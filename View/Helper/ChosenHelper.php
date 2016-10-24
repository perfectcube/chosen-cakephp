<?php

/**
 * This file is part of the Chosen CakePHP Plugin.
 *
 * Copyright (c) Paul Redmond - https://github.com/paulredmond
 *
 * @link https://github.com/paulredmond/chosen-cakephp
 * @license http://paulredmond.mit-license.org/ The MIT License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Chosen Helper File
 *
 * @package chosen
 * @subpackage chosen.views.helpers
 */
class ChosenHelper extends AppHelper
{
    public $helpers = array('Html', 'Form');

    /**
     * Default configuration options.
     *
     * Settings configured Configure class, ie. `Configure::write('Chosen.asset_base', '/path');`
     * take precedence over settings configured through Controller::$helpers property.
     */
    public $settings = array(
        'framework' => 'jquery',
        'class' => 'chosen-select',
        'asset_base' => '/chosen/chosen',
    );

    /**
     * If a chosen select element was called, load up the scripts.
     *
     * @var Boolean
     */
    private $load = false;

    /**
     * If the scripts were loaded.
     *
     * @var Boolean
     */
    private $loaded = false;

    /**
     * Determine if debug is disabled/enabled
     *
     * @var Boolean
     */
    private $debug = false;

    public function __construct(View $view, $settings = array())
    {
        parent::__construct($view, $settings);

        // @todo - this is merged by Helper::__construct() in 2.3.
        $this->settings = array_merge($this->settings, (array) $settings, (array) Configure::read('Chosen'));
        $this->debug = Configure::read('debug') ? true : false;

        if (!$this->isSupportedFramework($fw = $this->getSetting('framework'))) {
            throw new LogicException(sprintf('Configured JavaScript framework "%s" is not supported. Only "jquery" or "prototype" are valid options.', $fw));
        }
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getSetting($setting)
    {
        if (isset($this->settings[$setting])) {
            return $this->settings[$setting];
        }

        return null;
    }

    public function getDebug()
    {
        return (boolean) $this->debug;
    }

    public function getLoadStatus()
    {
        return (bool) $this->load;
    }

    /**
     * Chosen select element.
     */
    public function select($name, $options = array(), $attributes = array())
    {
        if (false === $this->load) {
            $this->load = true;
        }

        $class = $this->getSetting('class');

        // Use these locally to do some checking...still pass attributes to FormHelper.
        $multiple = isset($attributes['multiple']) ? $attributes['multiple'] : false;
        $deselect = isset($attributes['deselect']) ? $attributes['deselect'] : false;

        // Chosen only supports deselect on single selects.
        // @todo write a test and configure
        if ($deselect === true && $multiple === false) {
            $class .= '-deselect';
            unset($attributes['deselect']);
        }

        if (isset($attributes['class']) === false) {
            $attributes['class'] = $class;
        }
        else if (strstr($attributes['class'], $class) === false) {
            $attributes['class'] .= " {$class}";
        }

		$hide_label = (
			isset($attributes['label'])
			&& $attributes['label'] !== false
		) ? true : false;
		
		if(!$hide_label){
			// Default to the passed in select name
			$label = Inflector::humanize($name);
			// We have a configured label if:
			$override_label = (
				isset($attributes['label'])
				&& !empty($attributes['label'])
			) ? true : false;
			// since we have a label 
			if($override_label){
				// use the label
				$label = $attributes['label'];
			}	
		}

		Dev::speek($attributes);
		
		$arguments = array(
			'type'=>'select', 
			'options'=>$options
		);

		// dont alow options or type to be passed in through $attributes
		unset($attributes['type'],$attributes['options']);
		// merge in the required type and options keys 
		$arguments = array_merge($attributes,$arguments);
		Dev::speek($arguments);	
		// $rendered = $this->Form->select($name, $options, $attributes);
		$rendered = $this->Form->input($name, $arguments);
		
		Dev::speek($rendered);
        return $rendered;

    }

    public function afterRender($viewFile)
    {
        if (false === $this->load) {
            return;
        }

        $this->loadScripts();
    }

    public function loadScripts()
    {
        if ($this->loaded) {
            return;
        }

        $this->loaded = true;
        $base = $this->getsetting('asset_base');

        switch ($this->getSetting('framework')) {
            case 'prototype':
                $elm = 'prototype-script';
                $script = 'chosen.proto.%s';
            break;

            case 'jquery':
            default:
                $elm = 'jquery-script';
                $script = 'chosen.jquery.%s';
            break;
        }

        // 3rd party assets.
        $script = sprintf($script, $this->debug === true ? 'js' : 'min.js');
        $this->Html->css($base . '/chosen.css', null, array('inline' => false));
        $this->Html->script($base . '/' . $script, array('inline' => false));

        // Add the script.
        $this->_View->append('script', $this->getElement($elm));
    }

    /**
     * Gets the Plugin's element file based on JS framework being used.
     *
     * @param $element string Name of the plugin element to use.
     * @return string rendered javascript block based on the JS framework element.
     */
    protected function getElement($element)
    {
        $class = $this->getSetting('class');

        return $this->_View->element('Chosen.' . $element, array('class' => $class));
    }

    /**
     * Test if a JS framework is supported by this helper.
     *
     * @param $val The 'framework' setting must use a supported framework.
     *
     * @return bool
     */
    public function isSupportedFramework($val)
    {
        return in_array($val, array('jquery', 'prototype'));
    }
}
