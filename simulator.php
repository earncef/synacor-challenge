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
        $op = $this->getNextMemoryValue();

        if ($op == 0) {
            exit;
        } elseif ($op == 1) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $this->{"r$a"} = $b;
        } elseif ($op == 2) {
            $a = $this->getNextMemoryValue();
            array_push($this->stack, $a);
        } elseif ($op == 3) {
            $a = $this->getNextMemoryValue(true);
            if (empty($this->stack)) throw new Exception('Empty stack');
            $this->{"r$a"} = array_pop($this->stack);
        } elseif ($op == 4) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = ($b == $c) ? 1: 0;
        } elseif ($op == 5) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = ($b > $c) ? 1: 0;
        } elseif ($op == 6) {
            $a = $this->getNextMemoryValue();
            $this->position = $a - 1;
        } elseif ($op == 7) {
            $a = $this->getNextMemoryValue();
            $b = $this->getNextMemoryValue();
            if ($a != 0) $this->position = $b - 1;
        } elseif ($op == 8) {
            $a = $this->getNextMemoryValue();
            $b = $this->getNextMemoryValue();
            if ($a == 0) $this->position = $b - 1;
        } elseif ($op == 9) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = ($b + $c) % 32768;
        } elseif ($op == 10) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = ($b * $c) % 32768;
        } elseif ($op == 11) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = $b % $c;
        } elseif ($op == 12) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = $b & $c;
        } elseif ($op == 13) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $c = $this->getNextMemoryValue();
            $this->{"r$a"} = $b | $c;
        } elseif ($op == 14) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $this->{"r$a"} = 32767 & ~ $b;
        } elseif ($op == 15) {
            $a = $this->getNextMemoryValue(true);
            $b = $this->getNextMemoryValue();
            $this->{"r$a"} = $this->memory[$b];
        } elseif ($op == 16) {
            $a = $this->getNextMemoryValue();
            $b = $this->getNextMemoryValue();
            $this->memory[$a] = $b;
        } elseif ($op == 17) {
            $a = $this->getNextMemoryValue();
            array_push($this->stack, $this->position + 1);
            $this->position = $a - 1;
        } elseif ($op == 18) {
            if (empty($this->stack)) exit;
            $a = array_pop($this->stack);
            $this->position = $a - 1;
        } elseif ($op == 19) {
            $a = $this->getNextMemoryValue();
            echo chr($a);
        } elseif ($op == 20) {
            $a = $this->getNextMemoryValue(true);
            if ($this->input == '') {
                $this->input = fgets(STDIN);
            }
            $this->{"r$a"} = ord($this->input[0]);
            $this->input = substr($this->input, 1);
        } elseif ($op == 21) {
        } else {
            echo "Oops!!! Something has gone wrong. You are not supposed to be here";
            exit;
        }
    }

    protected function getNextMemoryValue($register = false) {
        $value = $this->memory[++$this->position];
        if ($value < 32768) {
            return $value;
        } elseif ($value < 32776) {
            $value %= 32768;
            if ($register) return $value;
            return $this->{"r$value"};
        }
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
