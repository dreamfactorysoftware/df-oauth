# Google OAuth with Group-to-Role Mapping Setup Guide

This guide explains how to configure Google OAuth in DreamFactory with automatic role assignment based on Google Workspace group membership.

## Prerequisites

- A Google Workspace account (not a personal Gmail account)
- Admin access to Google Cloud Console
- Admin access to Google Workspace Admin Console
- DreamFactory instance with the Google OAuth service type available

---

## Part 1: Google Cloud Console Setup

### Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Click the project dropdown at the top and select **New Project**
3. Enter a project name (e.g., "DreamFactory OAuth") and click **Create**
4. Select your new project from the dropdown

### Step 2: Enable Required APIs

1. Go to **APIs & Services** → **Library**
2. Search for and enable the following APIs:
   - **Admin SDK API** (required for group lookups)

### Step 3: Configure OAuth Consent Screen

1. Go to **APIs & Services** → **OAuth consent screen** → **Get Started**
2. Fill in the required fields:
   - **App name**: Your application name
   - **User support email**: Your email
   - **Developer contact email**: Your email
3. Select **Internal** (for Google Workspace users only) or **External**
4. Click **Save and Continue**

### Step 4: Add OAuth Scopes

1. On the Scopes page, click **Add or Remove Scopes**
2. Add the following scopes:
   - `openid`
   - `email`
   - `profile`
   - `https://www.googleapis.com/auth/admin.directory.group.readonly`
3. Click **Update** then **Save and Continue**

### Step 5: Create OAuth Credentials

1. Go to **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth client ID**
3. Select **Web application**
4. Enter a name (e.g., "DreamFactory")
5. Under **Authorized redirect URIs**, add:
   ```
   https://your-dreamfactory-domain.com/api/v2/{service-name}/sso
   ```
   Replace `{service-name}` with what you'll name your Google OAuth service in DreamFactory (e.g., `google_oauth`) this name must end with _oauth
6. Click **Create**
7. **Save the Client ID and Client Secret** - you'll need these for DreamFactory and the secret cannot be recovered so ensure you save it securely

---

## Part 2: Google Workspace Admin Console Setup

Domain-wide delegation allows the OAuth flow to access group membership information.

### Step 1: Access API Controls

1. Go to [Google Workspace Admin Console](https://admin.google.com)
2. Navigate to **Security** → **Access and data control** → **API controls** (you can also just search for API controls to navigate directly)

### Step 2: Configure Domain-Wide Delegation

1. Scroll down to **Domain-wide delegation**
2. Click **Manage Domain Wide Delegation**
3. Click **Add new**
4. Enter the **Client ID** from your OAuth credentials (the numeric ID, not the full client ID string)
   - You can find this in Google Cloud Console → APIs & Services → Credentials → Click on your OAuth client
5. In **OAuth scopes**, enter:
   ```
   https://www.googleapis.com/auth/admin.directory.group.readonly
   ```
6. Click **Authorize**

---

## Part 3: DreamFactory Configuration

### Step 1: Create Google OAuth Service

1. Log into DreamFactory as an administrator
2. Go to **Security** → **Authentication** → **+ Symbol to Create**
3. Select **Google OAuth**
4. Configure the service:
   - **Name**: `google_oauth` (must match the redirect URI you configured)
   - **Label**: Google OAuth
   - **Client ID**: Paste from Google Cloud Console
   - **Client Secret**: Paste from Google Cloud Console
   - **Redirect URL**: `https://your-dreamfactory-domain.com/api/v2/google_oauth/sso`

### Step 2: Enable Group-to-Role Mapping

1. In the service configuration, enable **Map Google Groups to Roles**
2. Be sure to set a **Default Role** (used when no group mapping matches)

### Step 3: Configure Group Mappings

1. In the **Google Group to Role Mapping** section, click **Add**
2. For each mapping:
   - **Role**: Select the DreamFactory role to assign
   - **Google Group Email**: Enter the full email address of the Google group (e.g., `developers@yourdomain.com`)
3. Add as many mappings as needed
4. Click **Save**

---
