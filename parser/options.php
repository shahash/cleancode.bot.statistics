<?php

function parse_options($optionsParams)
{

        $shortOptions = '';
        $longOptions = array();

        $requiredOptions = array();

        $argumentTypes = array('optional' => '::', 'required' => ':', 'no_required' => '');

        $shortToLongMapping = array();

        foreach($optionsParams as $optionName => $optionParam)
        {
                if($optionParam['short_option'])
                {
                        $shortOptions .= $optionParam['short_option'] . $argumentTypes[$optionParam['argument']];
                        $shortToLongMapping[$optionParam['short_option']] = $optionName;
                }

                $longOptions[] = $optionName . $argumentTypes[$optionParam['argument']];

                if($optionParam['required'])
                        $requiredOptions[$optionName] = false;
        }
        
//Checking for success parsing
        if(!$options = getopt($shortOptions, $longOptions))
        {
                return false;
        }
        
//Parsing from SHORT to LONG parametrs        
        foreach($options as $currentOption=>$optionValue)
        {
                if(isset($shortToLongMapping[$currentOption]))
                {
                        $options[$shortToLongMapping[$currentOption]] = $optionValue;
                        unset($options[$currentOption]);
                }
        }

//Checking for types and requireds
        foreach($options as $currentOption=>$optionValue)
        {
                $success = true;
                if($optionsParams[$currentOption]['argument'] == 'required' || ($optionsParams[$currentOption]['argument'] == 'optional' && $optionValue))
                {
                        switch($optionsParams[$currentOption]['type'])
                        {
                                case 'enum':
                                        if(!in_array($optionValue, $optionsParams[$currentOption]['possible_enum_values']))
                                                $options[$currentOption] = $optionsParams[$currentOption]['possible_enum_values'][0];
                                        break;
                                case 'int':
                                        if(!is_numeric($optionValue))
                                        {
                                                $success = false;
                                                unset($options[$currentOption]);
                                        }
                                        break;
                                case 'any':
                                default:
                                        //do anything
                                        break;
                        }
                }
                if($success && isset($requiredOptions[$currentOption]))
                        $requiredOptions[$currentOption] = true;
        }

        if(array_search(false, $requiredOptions))
                return false;

// Default value for option without args
		foreach($options as $currentOption=>$optionValue)
        {
        	if($option[$currentOption]==false && isset($optionParams[$currentOption]['default']))
        		$options[$currentOption]=$optionParams[$currentOption]['default'];
        }


        return $options;
}
