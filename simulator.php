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

    protected $filename;
    protected $position = -1;
    protected $memSize = 0;
    protected $input = "";

    public function __construct($filename) {
        $this->filename = $filename;
    }

    public function run() {
        $this->loadProgramToMemory();
        while ($this->position < $this->memSize) {
            $this->nextOp();
        }
    }

    protected function nextOp() {
        $op = $this->getMemoryValues(1);

        if ($op == 0) {
            exit;
        } elseif ($op == 1) {
            list($a, $b) = $this->getMemoryValues(2, true);
            $this->setRegister($a, $b);
        } elseif ($op == 2) {
            $a = $this->getMemoryValues(1);
            array_push($this->stack, $a);
        } elseif ($op == 3) {
            $a = $this->getMemoryValues(1, true);
            if (empty($this->stack)) throw new Exception('Empty stack');
            $this->setRegister($a, array_pop($this->stack));
        } elseif ($op == 4) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, ($b == $c) ? 1: 0);
        } elseif ($op == 5) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, ($b > $c) ? 1: 0);
        } elseif ($op == 6) {
            $a = $this->getMemoryValues(1);
            $this->position = $a - 1;
        } elseif ($op == 7) {
            list($a, $b) = $this->getMemoryValues(2);
            if ($a != 0) $this->position = $b - 1;
        } elseif ($op == 8) {
            list($a, $b) = $this->getMemoryValues(2);
            if ($a == 0) $this->position = $b - 1;
        } elseif ($op == 9) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, ($b + $c) % 32768);
        } elseif ($op == 10) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, ($b * $c) % 32768);
        } elseif ($op == 11) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, $b % $c);
        } elseif ($op == 12) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, $b & $c);
        } elseif ($op == 13) {
            list($a, $b, $c) = $this->getMemoryValues(3, true);
            $this->setRegister($a, $b | $c);
        } elseif ($op == 14) {
            list($a, $b) = $this->getMemoryValues(2, true);
            $this->setRegister($a, 32767 & ~ $b);
        } elseif ($op == 15) {
            list($a, $b) = $this->getMemoryValues(2, true);
            $this->setRegister($a, $this->memory[$b]);
        } elseif ($op == 16) {
            list($a, $b) = $this->getMemoryValues(2);
            $this->memory[$a] = $b;
        } elseif ($op == 17) {
            $a = $this->getMemoryValues(1);
            array_push($this->stack, $this->position + 1);
            $this->position = $a - 1;
        } elseif ($op == 18) {
            if (empty($this->stack)) exit;
            $a = array_pop($this->stack);
            $this->position = $a - 1;
        } elseif ($op == 19) {
            $a = $this->getMemoryValues(1);
            echo chr($a);
        } elseif ($op == 20) {
            $a = $this->getMemoryValues(1, true);
            if ($this->input == '') {
                $this->input = fgets(STDIN);
            }
            $this->setRegister($a, ord($this->input[0]));
            $this->input = substr($this->input, 1);
        } elseif ($op == 21) {
            //noop
        } else {
            throw new Exception('Oops!!! Something has gone wrong. You are not supposed to be here');
        }
    }
    
    protected function setRegister($r, $value) {
        $this->{"r$r"} = $value;
    }
    
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
