<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2015 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

namespace think\console\output\question;

use think\console\output\Question;

class Choice extends Question
{

    private $choices;
    private $multiselect  = false;
    private $prompt       = ' > ';
    private $errorMessage = 'Value "%s" is invalid';

    /**
     * Constructor
     * @param string $question problem
     * @param array  $choices  Options
     * @param mixed  $default  Default answer
     */
    public function __construct($question, array $choices, $default = null)
    {
        parent::__construct($question, $default);

        $this->choices = $choices;
        $this->setValidator($this->getDefaultValidator());
        $this->setAutocompleterValues($choices);
    }

    /**
     * Optional
     * @return array
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * Whether the setting can be multi-selected
     * @param bool $multiselect
     * @return self
     */
    public function setMultiselect($multiselect)
    {
        $this->multiselect = $multiselect;
        $this->setValidator($this->getDefaultValidator());

        return $this;
    }

    public function isMultiselect()
    {
        return $this->multiselect;
    }

    /**
     * Get tips
     * @return string
     */
    public function getPrompt()
    {
        return $this->prompt;
    }

    /**
     * Set up tips
     * @param string $prompt
     * @return self
     */
    public function setPrompt($prompt)
    {
        $this->prompt = $prompt;

        return $this;
    }

    /**
     * Set error message
     * @param string $errorMessage
     * @return self
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessage = $errorMessage;
        $this->setValidator($this->getDefaultValidator());

        return $this;
    }

    /**
     * Get the default authentication method
     * @return callable
     */
    private function getDefaultValidator()
    {
        $choices      = $this->choices;
        $errorMessage = $this->errorMessage;
        $multiselect  = $this->multiselect;
        $isAssoc      = $this->isAssoc($choices);

        return function ($selected) use ($choices, $errorMessage, $multiselect, $isAssoc) {
            // Collapse all spaces.
            $selectedChoices = str_replace(' ', '', $selected);

            if ($multiselect) {
                // Check for a separated comma values
                if (!preg_match('/^[a-zA-Z0-9_-]+(?:,[a-zA-Z0-9_-]+)*$/', $selectedChoices, $matches)) {
                    throw new \InvalidArgumentException(sprintf($errorMessage, $selected));
                }
                $selectedChoices = explode(',', $selectedChoices);
            } else {
                $selectedChoices = [$selected];
            }

            $multiselectChoices = [];
            foreach ($selectedChoices as $value) {
                $results = [];
                foreach ($choices as $key => $choice) {
                    if ($choice === $value) {
                        $results[] = $key;
                    }
                }

                if (count($results) > 1) {
                    throw new \InvalidArgumentException(sprintf('The provided answer is ambiguous. Value should be one of %s.', implode(' or ', $results)));
                }

                $result = array_search($value, $choices);

                if (!$isAssoc) {
                    if (!empty($result)) {
                        $result = $choices[$result];
                    } elseif (isset($choices[$value])) {
                        $result = $choices[$value];
                    }
                } elseif (empty($result) && array_key_exists($value, $choices)) {
                    $result = $value;
                }

                if (empty($result)) {
                    throw new \InvalidArgumentException(sprintf($errorMessage, $value));
                }
                array_push($multiselectChoices, $result);
            }

            if ($multiselect) {
                return $multiselectChoices;
            }

            return current($multiselectChoices);
        };
    }
}
