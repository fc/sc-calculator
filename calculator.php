<?php


abstract class Operator {
    abstract public function solve($a, $b);
}

class AdditionOperator extends Operator {
    public $precedence = 1;
    public function solve($a, $b) {
        return $a+$b;
    }
}

class SubtractionOperator extends Operator {
    public $precedence = 1;
    public function solve($a, $b) {
        return $a-$b;
    }
}

class MultiplicationOperator extends Operator {
    public $precedence = 3;
    public function solve($a, $b) {
        return $a*$b;
    }
}

class DivisionOperator extends Operator {
    public $precedence = 3;
    public function solve($a, $b) {
        if ($b==0) {
            throw new Exception('Division by zero');
        }
        return $a/$b;
    }
}

class ModulusOperator extends Operator {
    public $precedence = 3;
    public function solve($a, $b) {
        return $a%$b;
    }
}

class Calculator {

    private $operators = [];


    public function __construct() {
        $this->defineOperator('-', 'SubtractionOperator');
        $this->defineOperator('+', 'AdditionOperator');
        $this->defineOperator('*', 'MultiplicationOperator');
        $this->defineOperator('/', 'DivisionOperator');
    }

    public function defineOperator($op, $class) {
        if (class_exists($class)) {
            $this->operators[ $op ] = new $class();
        } else {
            throw new Exception('Invalid class: '. $class);
        }
    }

    // Refer to reverse polish notation / postfix algorithm:
    // https://en.wikipedia.org/wiki/Reverse_Polish_notation
    private function calcPostfix(array $postfix) {
        $stack = [];
        foreach ($postfix as $value) {
            if (is_numeric($value)) {
                $stack[] = $value;
            } else {
                $a = array_pop( $stack );
                $b = array_pop( $stack );

                if (isset($this->operators[ $value ])) {
                    $solver = $this->operators[ $value ];
                    $result = $solver->solve($b, $a);
                } else {
                    throw new Exception('Invalid operator "'. $value . '"');
                }
                $stack[] = $result;
            }
        }
        if (count($stack)!=1) {
            throw new Exception('Invalid expression');
        }
        return array_pop($stack);
    }

    // Refer to the shunting-yard algorithm:
    // https://en.wikipedia.org/wiki/Shunting-yard_algorithm
    private function infixToPostfix($infix) {
        $ops = preg_quote(implode('', array_keys($this->operators)));
        $operatorStack = [];
        $resultStack = [];
        foreach ($infix as $value) {

            if (is_numeric($value)) {
                $resultStack[] = $value;
            } else if (preg_match('~^['.$ops.']$~', $value)) {

                $length = count($operatorStack);
                if ($length>0) {
                    $top = $operatorStack[ $length-1 ];
                    // if item on stack has a higher precedence then pop and push the top operator
                    // and move onto our result stack
                    
                    
                    if ($this->operators[$value]->precedence <= $this->operators[$top]->precedence) {
                        $operator = array_pop( $operatorStack );
                        $resultStack[] = $operator;
                    }
                }
                $operatorStack[] = $value;
            }
        }

        $operatorStack = array_reverse($operatorStack);
        foreach ($operatorStack as $operator) {
            $resultStack[] = $operator;
        }

        return $resultStack;

    }

    public function solve($expr) {

        echo 'Expression: ' . $expr . "\n";
        $expr = trim($expr);

        $ops = preg_quote(implode('', array_keys($this->operators)));
        //echo $ops . "\n";
        // Is Valid expresssion
        if (!preg_match('~^-?\d+(\.\d+)?\s*(['.$ops.']\s*-?\d+(\.\d+)?\s*)*$~', trim($expr))) {
            throw new Exception('Invalid expression');
        }

        $tokens = preg_split("~([$ops])~", $expr, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $tokens = array_map('trim', $tokens);

        // fix negatives
        $count = count($tokens);
        for ( $x=0 ; $x<$count; ) {

            if ($tokens[$x]=='-') {
                $tokens[ $x+1 ] = '-' . $tokens[ $x+1];
                unset($tokens[ $x ]);
                $x = $x+1;
            }

            $x += 2;

        }

        // reassign keys due to unset above
        $tokens = array_values($tokens);

        $postfixStack = $this->infixToPostfix( $tokens );
        return $this->calcPostfix( $postfixStack );

    }

}



$c = new Calculator();
$c->defineOperator('%', 'ModulusOperator');
echo $c->solve('11 % 2'). "\nExpected: 1\n";
echo "\n";

echo $c->solve('-122.123 - -50 * 100 / 10'). "\n";
echo $c->solve('100-50'). "\nExpected: 50\n";
echo $c->solve('-50'). "\nExpected: -50\n";
try { 
echo $c->solve('fasdfasdf'). "\nExpected: false (0)\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
echo $c->solve('10 * 10 / 100 / 1'). "\nExpected: 1\n";
echo $c->solve('10 / 10 + 100'). "\nExpected: 101\n";
echo $c->solve('10 * 10 * 100'). "\nExpected: 10000\n";
echo $c->solve('10 * 10 * 100 / 100'). "\nExpected: 100\n";
echo $c->solve('10 - 10 * 123 / 8'). "\nExpected: 143.75\n";


