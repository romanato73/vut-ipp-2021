<?php


namespace src\TestFrame;


class HTMLGenerator
{
    /**
     * @var string Default path for web
     */
    private string $path = "src/TestFrame/web";

    /**
     * @var string Output HTML
     */
    private string $output = "";

    /**
     * HTMLGenerator constructor.
     *
     * @param string $path Web path
     */
    public function __construct(string $path = '')
    {
        $this->path = $path;

        // Get header and css
        $header = file_get_contents($this->path . '/header.html');
        $css = file_get_contents($this->path . '/master.css');

        // Append header
        $this->output .= str_replace('{css}', $css, $header);
    }

    /**
     * Generate and output an HTML file.
     *
     * @param string $type Type of tests.
     */
    public function generate(string $type)
    {
        // Replace type
        $this->output = str_replace('{test_type}', $type, $this->output);

        // Get footer and js
        $footer = file_get_contents($this->path . '/footer.html');
        $js = file_get_contents($this->path . '/script.min.js');

        // Append footer
        $this->output .= str_replace('{js}', $js, $footer);

        echo $this->output;
    }

    /**
     * Add row into HTML table.
     *
     * @param array $data Data added into template.
     */
    public function generateRow(array $data)
    {
        $template = file_get_contents($this->path . '/template.html');
        foreach ($data as $key => $value) {
            $template = str_replace("{{$key}}", $value, $template);
        }

        $this->output .= $template;
    }
}