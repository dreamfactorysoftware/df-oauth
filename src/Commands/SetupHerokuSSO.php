<?php

namespace DreamFactory\Core\OAuth\Commands;

use DreamFactory\Core\Enums\DataFormats;
use DreamFactory\Core\Exceptions\NotFoundException;
use DreamFactory\Core\Models\Service;
use DreamFactory\Core\OAuth\Models\HerokuAddonSecretType;
use DreamFactory\Core\Utility\FileUtilities;
use DreamFactory\Core\Enums\Verbs;
use Illuminate\Console\Command;
use DreamFactory\Core\Facades\ServiceManager;
use Illuminate\Http\Response;

class SetupHerokuSSO extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'df:heroku-setup {--service-config=[]}
                                            {--format=json}
                                            {--service-mutable=false}
                                            {--service-deletable=false}';

    protected $hidden = true;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import resources from files.';

    protected $defaultConfig = [
        'name' => 'heroku-addon',
        'label' => 'Heroku Add-on SSO',
        'description' => 'Heroku integration support.',
        'is_active' => true,
        'type' => 'heroku_addon_sso',
        'config' => [
            'secret' => 'HEROKU_SSO_SECRET',
            'secret_type' => HerokuAddonSecretType::ENVIRONMENT,
        ],
    ];

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        try {
            $config = $this->getServiceConfig();

            if (Service::query()->where('name', '=', $config['name'])->exists()) {
                $this->info('Heroku already integrated with this instance');
                return 0;
            }

            $format = $this->option('format');
            $format = DataFormats::toNumeric($format);

            /** @var Response $result */
            $result = ServiceManager::handleRequest('system', Verbs::POST, 'service', [], [], json_encode(['resource' => [$config]]), $format, false);
            /** @var string|array $responseContent */
            $responseContent = $result->getContent();
            if ($result->getStatusCode() >= 300) {
                $this->error(print_r($responseContent, true));
                return 1;
            }

            $service = $this->getCreatedService($responseContent);

            $service->mutable = (int)$this->option('service-mutable');
            $service->deletable = (int)$this->option('service-mutable');
            $service->saveOrFail();

            $this->info('Heroku integration added!');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
        }
        return 0;
    }

    /**
     * @param array $responseContent
     * @return Service
     */
    protected function getCreatedService($responseContent) {
        $serviceId = $responseContent['resource'][0]['id'];
        /** @var Service $service */
        $service = Service::query()->find($serviceId);
        return $service;
    }

    /**
     * @return array
     * @throws NotFoundException
     */
    protected function getServiceConfig() {
        $config = $this->option('service-config');
        if (filter_var($config, FILTER_VALIDATE_URL)) {
            // need to download file
            $config = FileUtilities::importUrlFileToTemp($config);
        }

        if (is_file($config)) {
            $config = file_get_contents($config);
        }
        $result = json_decode($config, true);
        if (isset($result['type']) && $result['type'] !== $this->defaultConfig['type']) {
            $this->warn('The parameter type is not supported');
            $result['type'] = $this->defaultConfig['type'];
        }
        return array_replace_recursive(
            $this->defaultConfig,
            $result
        );
    }
}
