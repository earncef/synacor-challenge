<?php

class Simulator {

    protected $memory = [];
    protected $r0 = null;
    protected $r1 = null;
    protected $r2 = null;
    protected $r3 = null;
    protected $r4 = null;
    protected $r5 = null;
    protected $r6 = null;
    protected $r7 = null;
    protected $stack = [];
    protected $ops = ['halt', 'set', 'push', 'pop', 'eq', 'gt', 'jmp', 'lt', 'jf', 'add', 'mult', 'mod', 'and', 'or', 'not', 'rmem', 'wmem', 'call', 'ret', 'out', 'in', 'noop'];
    protected $opParams = [
        'halt' => 0, 
        'set'  => 2, 
        'push' => 1, 
        'pop'  => 1, 
        'eq'   => 3, 
        'gt'   => 3, 
        'jmp'  => 1, 
        'lt'   => 2, 
        'jf'   => 2, 
        'add'  => 3, 
        'mult' => 3, 
        'mod'  => 3, 
        'and'  => 3,
        'or'   => 3, 
        'not'  => 2, 
        'rmem' => 2, 
        'wmem' => 2, 
        'call' => 1, 
        'ret'  => 0, 
        'out'  => 1, 
        'in'   => 1, 
        'noop' => 0,
    ];

    protected $filename;
    protected $position = -1;
    protected $memSize = 0;
    protected $input = "";
    protected $log = false;

    public function __construct($filename) {
        $this->filename = $filename;
    }

    public function run() {
        $this->loadProgramToMemory();
        while ($this->position < $this->memSize - 1) {
            $this->nextOp();
        }
    }

    protected function nextOp() {
        if ($this->log) {
            return $this->log($op);
        }

        $op = $this->getMemoryValues(1);
        if (isset($this->ops[$op])) {
            return $this->{"_{$this->ops[$op]}"}();
        }
        throw new Exception('Oops!!! Something went wrong. You are not supposed to be here!');
    }
    
    protected function log() {
        $op = $this->memory[++$this->position];
        echo $this->position, ': ';
        if (!isset($this->ops[$op])) {
            echo $op, "\n";
            return;
        }
        
        echo $this->ops[$op], ' ';
        for ($i = 0; $i < $this->opParams[$this->ops[$op]]; $i++) {
            echo $this->memory[++$this->position], ' ';
        }
        echo "\n";
    }
    
    protected function _halt() {
        exit;
    }
    
    protected function _set() {
        list($a, $b) = $this->getMemoryValues(2, true);
        $this->setRegister($a, $b);
    } 
    
    protected function _push() {
        $a = $this->getMemoryValues(1);
        array_push($this->stack, $a);
    }
    
    protected function _pop() {
        $a = $this->getMemoryValues(1, true);
        if (empty($this->stack)) throw new Exception('Empty stack');
        $this->setRegister($a, array_pop($this->stack));
    }
    
    protected function _eq() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, ($b == $c) ? 1: 0);
    }
    
    protected function _gt() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, ($b > $c) ? 1: 0);
    }
    
    protected function _jmp() {
        $a = $this->getMemoryValues(1);
        $this->position = $a - 1;
    }
    
    protected function _lt() {
        list($a, $b) = $this->getMemoryValues(2);
        if ($a != 0) $this->position = $b - 1;
    }
    
    protected function _jf() {
        list($a, $b) = $this->getMemoryValues(2);
        if ($a == 0) $this->position = $b - 1;
    }
    
    protected function _add() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, ($b + $c) % 32768);
    }
    
    protected function _mult() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, ($b * $c) % 32768);
    }
    
    protected function _mod() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, $b % $c);
    }
    
    protected function _and() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, $b & $c);
    }
    
    protected function _or() {
        list($a, $b, $c) = $this->getMemoryValues(3, true);
        $this->setRegister($a, $b | $c);
    }

    protected function _not() {
        list($a, $b) = $this->getMemoryValues(2, true);
        $this->setRegister($a, 32767 & ~ $b);
    }
    
    protected function _rmem() {
        list($a, $b) = $this->getMemoryValues(2, true);
        $this->setRegister($a, $this->memory[$b]);
    }
    
    protected function _wmem() {
        list($a, $b) = $this->getMemoryValues(2);
        $this->memory[$a] = $b;        
    }
    
    protected function _call() {
        $a = $this->getMemoryValues(1);
        array_push($this->stack, $this->position + 1);
        $this->position = $a - 1;
    }
    
    protected function _ret() {
        if (empty($this->stack)) exit;
        $a = array_pop($this->stack);
        $this->position = $a - 1;
    }
    
    protected function _out() {
        $a = $this->getMemoryValues(1);
        echo chr($a);
    }
    
    protected function _in() {
        $a = $this->getMemoryValues(1, true);
        if ($this->input == '') {
            $this->input = fgets(STDIN);
        }
        $this->setRegister($a, ord($this->input[0]));
        $this->input = substr($this->input, 1);
    }
    
    protected function _noop() {
        // noop    
    } 
       
    protected function setRegister($r, $value) {
        $this->{"r$r"} = $value;
    }
    
    /**
     * Get either register addresses or data of length $count from memory
     * starting from the next memory position.
     * If register address is expected set $register to true for every
     * expected value. Otherwise, the value from the register is returned.
     * 
     * Example:
     * $this->getMemoryValue(2, true) will check the first value for 
     * valid register address.
     * $this->getMemoryValue(3, true, false, true) will check the first 
     * and third values for valid register address.
     * 
     * @param int $count
     * @param bool ...$register
     * @return array
     */
    protected function getMemoryValues($count) {
        $args = func_get_args();
        array_shift($args);
        
        for($i = 0, $values = []; $i < $count; $i++) {
            $value = $this->memory[++$this->position];
            if ($value < 32768) {
                $values[] = $value;
                continue;
            }
            $value %= 32768;
            if (isset($args[$i]) && $args[$i]) {
                $values[] = $value;
                continue;
            } 
            $values[] = $this->{"r$value"};   
        }

        if ($count == 1) {
            return current($values);
        }
        return $values;
    }

    protected function loadProgramToMemory() {
        $handle = fopen($this->filename, "rb");
        $memory = unpack('v*', fread($handle, filesize($this->filename)));
        fclose($handle);

        $i = 0;
        foreach($memory as $value) {
            $this->memory[$i++] = $value;
        }
        $this->memSize = count($this->memory);
   }

}

$simulator = new Simulator('challenge.bin');
$simulator->run();
