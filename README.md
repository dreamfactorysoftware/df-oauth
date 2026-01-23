# df-oauth
DreamFactory OAuth support.


## Overview

DreamFactory is a secure, self-hosted enterprise data access platform that provides governed API access to any data source, connecting enterprise applications and on-prem LLMs with role-based access and identity passthrough.

## Configure DreamFactory OAuth Connector

1. Log in to the DreamFactory admin interface.
2. Navigate to **Security** > **Authentication** > **Add Service**.
3. Choose the desired provider from the **Service Type** dropdown.
4. Enter the required details:
   - **Namespace:** Must end with `_oauth` (e.g., `github_oauth`).
   - **Label:** Example: `GitHub Sign In`. This label will be displayed on DreamFactory's login page as the text on the provider's sign-in button.
   - **Client ID:** Obtained from the provider's application settings.
   - **Client Secret:** Obtained from the provider's application settings.
   - **Redirect URL:** This must match the provider's application settings and the DreamFactory configuration. It should point to the `/sso` endpoint.
      - Example: If your namespace is `github_oauth`, the redirect URL should be:
        ```
        https://your-dreamfactory-instance.com/api/v2/github_oauth/sso
        ```
5. Save the configuration.
6. Log out, reload the page, and the new OAuth login option for the configured provider should be visible.  
