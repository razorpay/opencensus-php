<?php


namespace RZP\lib;


class TemplateEngine
{
    protected $left;
    protected $right;

    public function __construct($left = '{', $right = '}')
    {
        $this->left = $left;
        $this->right = $right;
    }

    public function render(string $template, array $params)
    {
        if(empty($params))
        {
            return $template;
        }

        // TODO: optimize this.
        $result = $template;
        foreach($params as $key => $value)
        {
            if (is_array($value))
            {
                continue;
            }
            $result = str_replace($this->arg($key), $value, $result);
        }

        return $result;
    }

    private function arg($key)
    {
        return $this->left.$key.$this->right;
    }
}
