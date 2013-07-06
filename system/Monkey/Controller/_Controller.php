<?php
/**
 * Created by JetBrains PhpStorm.
 * User: hyiyou
 * Date: 13-5-6
 * Time: 下午10:46
 * To change this template use File | Settings | File Templates.
 */

namespace Monkey\Controller;


class _Controller
{
    protected
        $maxForwards= 5,
        $actionStack=array()
    ;

    public function forward($moduleName, $actionName)
    {
        // replace unwanted characters
        $moduleName = preg_replace('/[^a-z0-9\-_]+/i', '', $moduleName);
        $actionName = preg_replace('/[^a-z0-9\-_]+/i', '', $actionName);

        if ($this->getActionStack()->getSize() >= $this->maxForwards)
        {
            // let's kill this party before it turns into cpu cycle hell
            $error = 'Too many forwards have been detected for this request (> %d)';
            $error = sprintf($error, $this->maxForwards);

            throw new sfForwardException($error);
        }

        $rootDir = sfConfig::get('sf_root_dir');
        $app     = sfConfig::get('sf_app');
        $env     = sfConfig::get('sf_environment');

        if (!sfConfig::get('sf_available') || sfToolkit::hasLockFile($rootDir.'/'.$app.'_'.$env.'.lck'))
        {
            // application is unavailable
            $moduleName = sfConfig::get('sf_unavailable_module');
            $actionName = sfConfig::get('sf_unavailable_action');

            if (!$this->actionExists($moduleName, $actionName))
            {
                // cannot find unavailable module/action
                $error = 'Invalid configuration settings: [sf_unavailable_module] "%s", [sf_unavailable_action] "%s"';
                $error = sprintf($error, $moduleName, $actionName);

                throw new sfConfigurationException($error);
            }
        }

        // check for a module generator config file
        sfConfigCache::getInstance()->import(sfConfig::get('sf_app_module_dir_name').'/'.$moduleName.'/'.sfConfig::get('sf_app_module_config_dir_name').'/generator.yml', true, true);

        if (!$this->actionExists($moduleName, $actionName))
        {
            // the requested action doesn't exist
            if (sfConfig::get('sf_logging_enabled'))
            {
                $this->getContext()->getLogger()->info(sprintf('{sfController} action "%s/%s" does not exist', $moduleName, $actionName));
            }

            // track the requested module so we have access to the data in the error 404 page
            $this->context->getRequest()->setAttribute('requested_action', $actionName);
            $this->context->getRequest()->setAttribute('requested_module', $moduleName);

            // switch to error 404 action
            $moduleName = sfConfig::get('sf_error_404_module');
            $actionName = sfConfig::get('sf_error_404_action');

            if (!$this->actionExists($moduleName, $actionName))
            {
                // cannot find unavailable module/action
                $error = 'Invalid configuration settings: [sf_error_404_module] "%s", [sf_error_404_action] "%s"';
                $error = sprintf($error, $moduleName, $actionName);

                throw new sfConfigurationException($error);
            }
        }

        // create an instance of the action
        $actionInstance = $this->getAction($moduleName, $actionName);

        // add a new action stack entry
        $this->getActionStack()->addEntry($moduleName, $actionName, $actionInstance);

        // include module configuration
        require(sfConfigCache::getInstance()->checkConfig(sfConfig::get('sf_app_module_dir_name').'/'.$moduleName.'/'.sfConfig::get('sf_app_module_config_dir_name').'/module.yml'));

        // check if this module is internal
        if ($this->getActionStack()->getSize() == 1 && sfConfig::get('mod_'.strtolower($moduleName).'_is_internal') && !sfConfig::get('sf_test'))
        {
            $error = 'Action "%s" from module "%s" cannot be called directly';
            $error = sprintf($error, $actionName, $moduleName);

            throw new sfConfigurationException($error);
        }

        if (sfConfig::get('mod_'.strtolower($moduleName).'_enabled'))
        {
            // module is enabled

            // check for a module config.php
            $moduleConfig = sfConfig::get('sf_app_module_dir').'/'.$moduleName.'/'.sfConfig::get('sf_app_module_config_dir_name').'/config.php';
            if (is_readable($moduleConfig))
            {
                require_once($moduleConfig);
            }

            // initialize the action
            if ($actionInstance->initialize($this->context))
            {
                // create a new filter chain
                $filterChain = new sfFilterChain();
                $this->loadFilters($filterChain, $actionInstance);

                if ($moduleName == sfConfig::get('sf_error_404_module') && $actionName == sfConfig::get('sf_error_404_action'))
                {
                    $this->getContext()->getResponse()->setStatusCode(404);
                    $this->getContext()->getResponse()->setHttpHeader('Status', '404 Not Found');

                    foreach (sfMixer::getCallables('sfController:forward:error404') as $callable)
                    {
                        call_user_func($callable, $this, $moduleName, $actionName);
                    }
                }

                // change i18n message source directory to our module
                if (sfConfig::get('sf_i18n'))
                {
                    if (sfLoader::getI18NDir($moduleName))
                    {
                        $this->context->getI18N()->setMessageSourceDir(sfLoader::getI18NDir($moduleName), $this->context->getUser()->getCulture());
                    }
                    else
                    {
                        $this->context->getI18N()->setMessageSourceDir(sfConfig::get('sf_app_i18n_dir'), $this->context->getUser()->getCulture());
                    }
                }

                // process the filter chain
                $filterChain->execute();
            }
            else
            {
                // action failed to initialize
                $error = 'Action initialization failed for module "%s", action "%s"';
                $error = sprintf($error, $moduleName, $actionName);

                throw new sfInitializationException($error);
            }
        }
        else
        {
            // module is disabled
            $moduleName = sfConfig::get('sf_module_disabled_module');
            $actionName = sfConfig::get('sf_module_disabled_action');

            if (!$this->actionExists($moduleName, $actionName))
            {
                // cannot find mod disabled module/action
                $error = 'Invalid configuration settings: [sf_module_disabled_module] "%s", [sf_module_disabled_action] "%s"';
                $error = sprintf($error, $moduleName, $actionName);

                throw new sfConfigurationException($error);
            }

            $this->forward($moduleName, $actionName);
        }
    }
}