<?php


namespace src\Analyzer;


use DOMDocument;
use DOMElement;
use src\Support\Exception;

class XMLGenerator
{
    /**
     * @var DOMDocument XML object
     */
    private DOMDocument $xml;

    /**
     * Creates new DOMDocument object.
     *
     * @param string $version  Set version for XML
     * @param string $encoding Set encoding for XML
     * @param bool   $format   Set if output should be formatted
     */
    public function __construct(string $version, string $encoding, bool $format)
    {
        $this->xml = new DOMDocument($version, $encoding);
        $this->xml->formatOutput = $format;
    }

    /**
     * Generates the whole XML document from registry.
     *
     * @param array $registry The registry from which we generate XML Document
     */
    public function generateXML(array $registry)
    {
        $root = $this->generateRootElement();

        // Append Root element
        $this->xml->appendChild($root);

        // Generate each instruction
        foreach ($registry as $order => $instruction) {
            $this->generateInstruction($root, $order, $instruction);
        }

        // Return XML file
        echo $this->xml->saveXML();
        echo PHP_EOL;
    }

    /**
     * Generates the root element <program>
     *
     * @return DOMElement|false DOMElement or false if an error occurred.
     */
    private function generateRootElement()
    {
        try {
            $root = $this->xml->createElement('program');

            if (!$root) throw new Exception("Can not create program element.", 99);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        $root->setAttribute('language', 'IPPcode21');

        return $root;
    }

    /**
     * Generates an instruction element.
     *
     * @param DOMElement $root        XML Root element
     * @param int        $order       Instruction order
     * @param array      $instruction Instruction array
     */
    private function generateInstruction(DOMElement $root, int $order, array $instruction)
    {
        // Increment order
        $order++;

        // Create XML friendly instruction
        $instruction = $this->createInstructionForXML($instruction);

        // Create new <instruction> element
        $instructionElement = $this->xml->createElement('instruction');

        try {
            if (!$instructionElement) throw new Exception("Can not create instruction element.", 99);
        } catch (Exception $exception) {
            die($exception->terminateProgram());
        }

        // Set attributes
        $instructionElement->setAttribute('order', $order);
        $instructionElement->setAttribute('opcode', $instruction['name']);

        // Generate operands
        $this->generateOperands($instructionElement, $instruction['operands']);

        // Append to root element
        $root->appendChild($instructionElement);
    }

    /**
     * Generates arg elements
     *
     * @param DOMElement $root     Root element
     * @param array      $operands Array of operands
     */
    private function generateOperands(DOMElement $root, array $operands)
    {
        foreach ($operands as $index => $operand) {
            $index++;

            // Problematic characters fix
            $operand['value'] = htmlspecialchars($operand['value']);

            // Generate operand element
            if ($operand['type'] == 'var') {
                // Operand type is var -> keep frame and @
                $varOperand = $operand['prefix'] . '@' . $operand['value'];
                $operandElement = $this->xml->createElement('arg' . $index, $varOperand);
            } else {
                $operandElement = $this->xml->createElement('arg' . $index, $operand['value']);
            }

            try {
                if (!$operandElement) throw new Exception("Can not create operand element.", 99);
            } catch (Exception $exception) {
                die($exception->terminateProgram());
            }

            $operandElement->setAttribute('type', $operand['type']);

            $root->appendChild($operandElement);
        }
    }

    /**
     * Creates instruction array with required data for XML generator.
     *
     * @param array $instruction Instruction array
     *
     * @return array Array with required instruction data
     */
    private function createInstructionForXML(array $instruction) : array
    {
        return [
            'name' => $instruction['name'],
            'operands' => $instruction['operands'],
        ];
    }
}