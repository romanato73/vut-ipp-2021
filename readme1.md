## Documentation of Project Implementation for IPP 2020/2021  
Name and surname: Roman Országh  
Login: xorsza01

## 1 Introduction
Project is using OOP approach - using classes and its methods.
I used this approach to improve my skills in OOP principles.
Script is using autoloader to load all required classes, which
are used in the project. Right from the beginning I divided
project into 3 phases - Parse arguments, Lexical and Syntax
analysis, XML generator.

## 2 File structure

### 2.1 Classes (src/Analyzer)
**App.php** - The main application takes care of arguments if they are correct.  
**Core.php** - Analyzer contains lexical and syntax analysis.  
**XMLGenerator.php** - Generates XML code from parsed instructions.

### 2.2 Traits (src/Analyzer/Traits)
For better orientation and simplicity I decided to use traits.
Traits are great in case we need to inherit multiple behaviours.

**Instructions.php** - Instructions registry and methods for working
with instructions (syntax checker for instruction, operand validation...).  
**Lexical.php** - Support methods for lexical analysis.  
**Token.php** - Token registry and methods for working with tokens.

### 2.3 Support (src/Support)

**Facade.php** - The main methods that are used in parse.php and also test.php  
**Exception.php** - Custom extension for PHP Exception Model.

### 2.4 Extension STATP (src/Extensions)
**Statistics.php** - This class takes care of statistics for extension STATP.
This file contains mainly static methods - so I do not need to create instances.

## 3 Implementation
### 3.1 Parse arguments
App Class contains the main method `listen(int, array)` that
listens for all entered arguments. This method is also checking
each argument if they exist in allowed arguments which are registered
inside `parse.php` and validate them.

### 3.2 Lexical and syntax analysis
After the file is loaded, program calls `lexicalAnalysis()` method
which reads lines from `STDIN`. In lexical analysis it creates
tokens and also checks if header is correct.

Tokens created in lexical analysis are passed into `syntaxAnalysis($tokens)`
method which checks the syntax. It checks whether there are more than 1
header, if there is a new line after each instruction and also
the number of operands for each instruction. If syntax analysis is without
errors, the registry of instructions is created.

### 3.3 XML Generator
In the last phase of program XML file is generated using method
`generateXML($registry)`, which takes the registry of instructions
created in syntax analysis. In case of XML special characters such as
`<, >, &, ", '` is used PHP built-in function `htmlspecialchars()` to convert them
into safe XML characters.

## 4 Extension STATP

In **(3.1) Parse arguments** statistics arguments are validated and initialized.  
In **(3.2) Lexical analysis** arguments: `--comments` and `--loc` are counted.  
In **(3.2) Syntax analysis** arguments `--jumps`, `--labels`, `--fwjumps`, `--backjumps`, `--badjumps` are counted.  
In phase **(3.3)** after generating XML file it also creates the file/s (if `--stats` argument passed)
where statistics are written.
