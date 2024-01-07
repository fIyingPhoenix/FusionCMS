<?php namespace MX;

use MX\CI;
use CI_Loader;

defined('BASEPATH') or exit('No direct script access allowed');

defined('EXT') or define('EXT', '.php');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 *
 * @link http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter CI_Loader class
 * and adds features allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Loader.php
 *
 * @copyright Copyright (c) 2015 Wiredesignz
 * @version   5.5
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class MX_Loader extends CI_Loader
{
    protected $module;
    protected $controller;

    public array $ci_plugins = [];
    public $ci_cached_vars = [];

    /**
     * [Initialize the loader variables]
     *
     * @method initialize
     *
     * @param bool|null $controller [description]
     *
     * @return void [type]                 [description]
     */
    public function initialize($controller = null)
    {
        /* set the module name */
        $this->module = CI::$APP->router->fetch_module();

        if ($controller instanceof MX_Controller) {
            /* reference to the module controller */
            $this->controller = $controller;

            /* references to ci loader variables */
            foreach (get_class_vars('CI_Loader') as $var => $val) {
                if ($var !== '_ci_ob_level') {
                    $this->$var =& CI::$APP->load->$var;
                }
            }
        } else {
            parent::initialize();

            /* autoload module items */
            $this->autoloader([]);
        }

        /* add this module path to the loader variables */
        $this->_add_module_paths($this->module);
    }

    /**
     * [Add a module path loader variables]
     *
     * @method _add_module_paths
     *
     * @param string $module [description]
     */
    public function _add_module_paths(string $module = '')
    {
        if (empty($module)) {
            return;
        }

        foreach (MX_Modules::$locations as $location => $offset) {
            /* only add a module path if it exists */
            if (is_dir($module_path = $location . $module . '/') && ! in_array($module_path, $this->_ci_model_paths)) {
                array_unshift($this->_ci_model_paths, $module_path);
            }
        }
    }

    /**
     * [Load a module config file]
     *
     * @method config
     *
     * @param [type]  $file            [description]
     * @param boolean $use_sections [description]
     * @param boolean $fail_gracefully [description]
     *
     * @return bool
     */
    public function config($file, $use_sections = false, $fail_gracefully = false)
    {
        return CI::$APP->config->load($file, $use_sections, $fail_gracefully);
    }

    /**
     * [Load the database drivers]
     *
     * @method database
     *
     * @param string $params [description]
     * @param boolean $return [description]
     * @param null $query_builder
     * @return false|mixed|MX_Loader
     */
    public function database($params = '', $return = false, $query_builder = null)
    {
        if (
            $return === false && $query_builder === null &&
            isset(CI::$APP->db) && is_object(CI::$APP->db) && ! empty(CI::$APP->db->conn_id)
        ) {
            return false;
        }

        require_once BASEPATH . 'database/DB' . EXT;

        if ($return === true) {
            return DB($params, $query_builder);
        }

        CI::$APP->db = DB($params, $query_builder);

        return $this;
    }

    /**
     * [Load a module helper]
     *
     * @method helper
     *
     * @param array  $helpers [description]
     *
     * @return [type]         [description]
     */
    public function helper($helpers = [])
    {
        if (is_array($helpers)) {
            return $this->helpers($helpers);
        }

        if (isset($this->_ci_helpers[$helpers])) {
            return;
        }
        // Backward function
        [$path, $module_helper] = MX_Modules::find($helpers . '_helper', $this->module, 'helpers/');

        if ($path === false) {
            return parent::helper($helpers);
        }

        MX_Modules::load_file($module_helper, $path);
        $this->_ci_helpers[$module_helper] = true;
        return $this;
    }

    /**
     * [Load an array of helpers]
     *
     * @method helpers
     *
     * @param array   $helpers [description]
     *
     * @return [type]           [description]
     */
    public function helpers($helpers = [])
    {
        foreach ($helpers as $helper) {
            $this->helper($helper);
        }
        return $this;
    }

    /**
     * [Load a module language file]
     *
     * @method language
     *
     * @param array    $langfile   [description]
     * @param string   $lang      [description]
     * @param boolean $return     [description]
     * @param boolean $add_suffix [description]
     * @param string $alt_path   [description]
     *
     * @return [type]               [description]
     */
    public function language($langFile, $lang = '', bool $return = false, bool $add_suffix = true, string $alt_path = '')
    {
        CI::$APP->lang->load($langFile, $lang, $return, $add_suffix, $alt_path);
        return $this;
    }

    /**
     * [languages description]
     *
     * @method languages
     *
     * @param [type]    $languages [description]
     *
     * @return [type]               [description]
     */
    public function languages($languages)
    {
        foreach ($languages as $language) {
            $this->language($language);
        }
        return $this;
    }

    /**
    * [Load a module library]
    *
    * @method library
    *
    * @param [type]  $library     [description]
    * @param [type]  $params      [description]
    * @param [type]  $object_name [description]
    *
    * @return [type]               [description]
    */
    public function library($library, $params = null, $object_name = null)
    {
        if (is_array($library)) {
            return $this->libraries($library);
        }

        $class = strtolower(basename($library));

        if (isset($this->_ci_classes[$class]) && $_alias = $this->_ci_classes[$class]) {
            return $this;
        }

        ($_alias = strtolower($object_name ?? '')) or $_alias = $class;

        // Backward function
        [$path, $module_library] = MX_Modules::find($library, $this->module, 'libraries/');

        /* load library config file as params */
        if ($params === null) {
            // Backward function
            [$path2, $file] = MX_Modules::find($_alias, $this->module, 'config/');
            $path2 && $params = MX_Modules::load_file($file, $path2, 'config');
        }

        if ($path === false) {
            $this->_ci_load_library($module_library, $params, $object_name);
        } else {
            MX_Modules::load_file($module_library, $path);

            $library = ucfirst($module_library);
            CI::$APP->$_alias = new $library($params);

            $this->_ci_classes[$class] = $_alias;
        }
        return $this;
    }

    /**
     * [Load an array of libraries]
     *
     * @method libraries
     *
     * @param [type]    $libraries [description]
     *
     * @return [type]               [description]
     */
    public function libraries($libraries)
    {
        foreach ($libraries as $library => $alias) {
            is_int($library) ? $this->library($alias) : $this->library($library, null, $alias);
        }
        return $this;
    }

    /**
     * [Load a module model]
     *
     * @method model
     *
     * @param [type]  $model       [description]
     * @param [type]  $object_name [description]
     * @param boolean $connect     [description]
     *
     * @return [type]               [description]
     */
    public function model($model, $object_name = null, $connect = false)
    {
        if (is_array($model)) {
            return $this->models($model);
        }

        ($_alias = $object_name) or $_alias = basename($model);

        if (in_array($_alias, $this->ci_models, true)) {
            return $this;
        }

        // Backward function
        [$path, $module_model] = MX_Modules::find(strtolower($model), $this->module, 'models/');

        if ($path === false) {
            /* check application & packages */
            parent::model($model, $object_name, $connect);
        } else {
            class_exists('CI_Model', false) or load_class('Model', 'core');

            if ($connect !== false && ! class_exists('CI_DB', false)) {
                if ($connect === true) {
                    $connect = '';
                }
                $this->database($connect, false, true);
            }

            MX_Modules::load_file($module_model, $path);

            $model = ucfirst($module_model);
            CI::$APP->$_alias = new $model();

            $this->ci_models[] = $_alias;
        }
        return $this;
    }

    /**
     * [Load an array of models]
     *
     * @method models
     *
     * @param [type] $models [description]
     *
     * @return [type]         [description]
     */
    public function models($models)
    {
        foreach ($models as $model => $alias) {
            is_int($model) ? $this->model($alias) : $this->model($model, $alias);
        }
        return $this;
    }

    /**
     * [Load a module controller]
     *
     * @method module
     *
     * @param [type] $module [description]
     * @param [type] $params [description]
     *
     * @return [type]         [description]
     */
    public function module($module, $params = null)
    {
        if (is_array($module)) {
            return $this->modules($module);
        }

        $_alias = strtolower(basename($module));
        CI::$APP->$_alias = MX_Modules::load(array($module => $params));
        return $this;
    }

    /**
     * [Load an array of controllers]
     *
     * @method modules
     *
     * @param [type]  $modules [description]
     *
     * @return [type]           [description]
     */
    public function modules($modules)
    {
        foreach ($modules as $module) {
            $this->module($module);
        }
        return $this;
    }

    /**
     * [Load a module plugin]
     *
     * @method plugin
     *
     * @param [type] $plugin [description]
     *
     * @return [type]         [description]
     */
    public function plugin($plugin)
    {
        if (is_array($plugin)) {
            return $this->plugins($plugin);
        }

        if (isset($this->ci_plugins[$plugin])) {
            return $this;
        }

        // Backward function
        [$path, $module_plugin] = MX_Modules::find($plugin . '_pi', $this->module, 'plugins/');

        if ($path === false && ! is_file($module_plugin = APPPATH . 'plugins/' . $module_plugin . EXT)) {
            show_error("Unable to locate the plugin file: {$module_plugin}");
        }

        MX_Modules::load_file($module_plugin, $path);
        $this->ci_plugins[$plugin] = true;
        return $this;
    }

    /**
     * [Load an array of plugins]
     *
     * @method plugins
     *
     * @param [type]  $plugins [description]
     *
     * @return [type]           [description]
     */
    public function plugins($plugins)
    {
        foreach ($plugins as $plugin) {
            $this->plugin($plugin);
        }
        return $this;
    }

    /**
     * [Load a module view]
     *
     * @method view
     *
     * @param [type]  $view   [description]
     * @param array   $vars   [description]
     * @param boolean $return [description]
     *
     * @return [type]          [description]
     */
    public function view($view, $vars = [], $return = false)
    {
        // Backward function
        [$path, $module_view] = MX_Modules::find($view, $this->module, 'views/');

        if ($path) {
            $this->ci_view_paths = [$path => true] + $this->ci_view_paths;
            $view = $module_view;
        }

        if (method_exists($this, '_ci_object_to_array')) {
            return $this->ci_load(['_ci_view' => $view, '_ci_vars' => $this->_ci_object_to_array($vars), '_ci_return' => $return]);
        } else {
            return $this->ci_load(['_ci_view' => $view, '_ci_vars' => $this->_ci_prepare_view_vars($vars), '_ci_return' => $return]);
        }
    }

    /**
     * [_ci_get_component description]
     *
     * @method _ci_get_component
     *
     * @param [type]            $component [description]
     *
     * @return [type]                       [description]
     */
    protected function &_ci_get_component($component)
    {
        return CI::$APP->$component;
    }

    /**
     * [__get description]
     *
     * @method __get
     *
     * @param [type] $class [description]
     *
     * @return [type]        [description]
     */
    public function __get($class)
    {
        return isset($this->controller) ? $this->controller->$class : CI::$APP->$class;
    }

    /**
     * [_ci_load description]
     *
     * @method _ci_load
     *
     * @param [type]   $_ci_data [description]
     *
     * @return [type]             [description]
     */
    public function ci_load($ci_data)
    {
        extract($ci_data);

        if (isset($ci_view)) {
            $ci_path = '';

            /* add file extension if not provided */
            $_ci_file = pathinfo($ci_view, PATHINFO_EXTENSION) ? $ci_view : $ci_view . EXT;

            foreach ($this->ci_view_paths as $path => $cascade) {
                if (file_exists($view = $path . $_ci_file)) {
                    $ci_path = $view;
                    break;
                }
                if (! $cascade) {
                    break;
                }
            }
        } elseif (isset($ci_path)) {
            $_ci_file = basename($ci_path);
            if (! file_exists($ci_path)) {
                $ci_path = '';
            }
        }

        if (empty($ci_path)) {
            show_error('Unable to load the requested file: ' . $_ci_file);
        }

        if (isset($ci_vars)) {
            $this->ci_cached_vars = array_merge($this->ci_cached_vars, (array) $ci_vars);
        }

        extract($this->ci_cached_vars);

        ob_start();

        if ((bool) @ini_get('short_open_tag') === false && CI::$APP->config->item('rewrite_short_tags')) {
            echo eval('?>' . preg_replace('/;*\s*\?>/', '; ?>', str_replace('<?=', '<?php echo ', file_get_contents($ci_path))));
        } else {
            include($ci_path);
        }

        log_message('debug', 'File loaded: ' . $ci_path);

        if ($ci_return === true) {
            return ob_get_clean();
        }

        if (ob_get_level() > $this->ci_ob_level + 1) {
            ob_end_flush();
        } else {
            CI::$APP->output->append_output(ob_get_clean());
        }
    }

    /**
     * [Autoload module items]
     *
     * @method autoloader
     *
     * @param [type]      $autoload [description]
     *
     * @return [type]                [description]
     */
    public function autoloader($autoload)
    {
        $path = false;

        if ($this->module) {
            // Backward function
            [$path, $file] = MX_Modules::find('constants', $this->module, 'config/');
            /* module constants file */
            if ($path !== false) {
                include_once $path . $file . EXT;
            }

            // Backward function
            [$path, $file] = MX_Modules::find('autoload', $this->module, 'config/');

            /* module autoload file */
            if ($path !== false) {
                $autoload = array_merge(MX_Modules::load_file($file, $path, 'autoload'), $autoload);
            }
        }

        /* nothing to do */
        if (count($autoload) === 0) {
            return;
        }

        /* autoload package paths */
        if (isset($autoload['packages'])) {
            foreach ($autoload['packages'] as $package_path) {
                $this->add_package_path($package_path);
            }
        }

        /* autoload config */
        if (isset($autoload['config'])) {
            foreach ($autoload['config'] as $config) {
                $this->config($config);
            }
        }

        /* autoload helpers, plugins, languages */
        foreach (array('helper', 'plugin', 'language') as $type) {
            if (isset($autoload[$type])) {
                foreach ($autoload[$type] as $item) {
                    $this->$type($item);
                }
            }
        }

        // Autoload drivers
        if (isset($autoload['drivers'])) {
            foreach ($autoload['drivers'] as $item => $alias) {
                is_int($item) ? $this->driver($alias) : $this->driver($item, $alias);
            }
        }

        /* autoload database & libraries */
        if (isset($autoload['libraries'])) {
            if (!CI::$APP->config->item('database') && in_array('database', $autoload['libraries'])) {
                $this->database();

                $autoload['libraries'] = array_diff($autoload['libraries'], array('database'));
            }

            /* autoload libraries */
            foreach ($autoload['libraries'] as $library => $alias) {
                is_int($library) ? $this->library($alias) : $this->library($library, null, $alias);
            }
        }

        /* autoload models */
        if (isset($autoload['model'])) {
            foreach ($autoload['model'] as $model => $alias) {
                is_int($model) ? $this->model($alias) : $this->model($model, $alias);
            }
        }

        /* autoload module controllers */
        if (isset($autoload['modules'])) {
            foreach ($autoload['modules'] as $controller) {
                ($controller != $this->module) && $this->module($controller);
            }
        }
    }
}

/**
 * load the CI class for Modular Separation 
**/
(class_exists('CI', false)) or require_once __DIR__ . '/Base.php';
