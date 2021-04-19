## Documentation of Project Implementation for IPP 2020/2021  
Name and surname: Roman Országh  
Login: xorsza01

## 1 Interpret
Project is using OOP approach - using classes and its methods.
I used this approach to improve my skills in OOP principles.
Firstly interpret parses the XML file to check if it's correct.
After XML file is checked, the storage is initialized and
instructions are collected. Interpret also creates an orders list,
which contains the order of the instructions then it executes
the first instruction from the list, and then it continues to another
instruction.

### 1.1 File structure
#### Classes (src/Interpret)
**App.py** - The main application takes care of arguments if they are correct.  
**Argument.py** - Class for registering and checking program arguments.  
**Core.py** - Interpret core, which checks XML file and executes all instructions.  
**Instruction.py** - Class that holds information about instruction.  
**Interfaces.py** - Main interfaces for instruction arguments and stack.  
**Parser.py** - XML file parser.  
**Storage.py** - The main storage for the application.  

#### Support (src/Support)
**DataHandler.py** - Collected instructions and errors.  
**ErrorHandler.py** - Handles all errors in the program.

### 1.2 Implementation
#### XML Parser
Interpret gets nodes from XML file then parser checks its validity.
Parser checks tag names, attributes and also instructions and its arguments.

### 1.3 Storage
The storage holds frames, stack, labels and calls.  
#### Frames
Frames class has 3 type of frames: Global, Temp, Locals. Locals frame type
is a list that holds all local frames in the program. The current
local frame is selected by using nesting variable. Each time local frame is
created nesting is incremented.  
#### Stack
Stack is a storage that holds all variables that are pushed into a stack.
Stack inherits StackInterface.
#### Labels
Labels are collected during orders list creation.  
#### Calls
Calls is a stack of called functions during interpretation.
Calls inherits StackInterface.

### 1.4 Instruction execution
The first instruction from orders list is executed as the first instruction.
After each instruction is executed, orders list is checked if it contains
more instructions otherwise the program is terminated with code 0 (success).

## 2 Test Frame

### 2.1 File structure
#### Classes (src/TestFrame)
**App.php** - The main application takes care of arguments if they are correct.  
**Core.php** - Test frame core contains scripts for testing parse and interpret.  
**HTMLGenerator.php** - HTML Generator generates results from tests as HTML output.  

#### Traits (src/TestFrame/Traits) 
**Traits/PathChecker.php** - Checks the paths of the tests.

### 2.2 Implementation
Test frame initializes HTMLGenerator with the defined path of the web templates.
The instance of HTMLGenerator is sent to the Core that modifies this HTML output.
The core of the test frame detects type of tests (parse, interpret, all) by the
passed arguments. Before running a test scripts, paths are set from the arguments
and directories are set using DirectoryIterator class. Each test is pushed into
an array of tests which is a 2D array where directory and each test is stored.
After collected tests each test is executed and output is generated at the end.
