<?php

namespace ClosureIt;

use Closure;
use ReflectionFunction;
use ReflectionParameter;

class Analyzer
{
    private $closure;
    private $reflection;

    private $tokens;
    private $params;
    private $variables;

    function __construct(Closure $closure)
    {
        $this->closure = $closure;
        $this->reflection = new ReflectionFunction($closure);

        $rawSource = $this->getRawSource();
        $rawTokens = $this->getRawTokens($rawSource);
        $this->checkRawTokens($rawTokens);

        $this->tokens = $this->getReturnTokens($rawTokens);
        $this->params = $this->getClosureParams();
        $this->variables = $this->getClosureVariables();
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function getVariables()
    {
        return $this->variables;
    }

    private function getRawSource()
    {
        $source = '';
        $file = $this->getFile();

        $startLine = $this->reflection->getStartLine() - 1;
        $endLine = $this->reflection->getEndLine();

        $file->seek($startLine);
        while ($file->key() < $endLine) {
            $source .= $file->current();
            $file->next();
        }

        return trim($source);
    }

    private function getClosureParams()
    {
        return array_map(function (ReflectionParameter $param) {
            return $param->getName();
        }, $this->reflection->getParameters());
    }

    private function getClosureVariables()
    {
        return $this->reflection->getStaticVariables();
    }

    private function getFile()
    {
        $fileName = $this->reflection->getFileName();
        if (!is_readable($fileName)) {
            throw new AnalyzerException("File '{$fileName}' is not readable.");
        }

        return new \SplFileObject($fileName);
    }

    private function getRawTokens($source)
    {
        if (strpos($source, '<?php') !== 0) {
            $source = "<?php\n" . $source;
        }

        return token_get_all($source);
    }

    private function checkRawTokens($tokens)
    {
        $returnCount = 0;
        $functionCount = 0;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] == T_FUNCTION) {
                    $functionCount++;
                } else {
                    if ($token[0] == T_RETURN) {
                        $returnCount++;
                    }
                }
            }
        }

        if ($functionCount < 1) {
            throw new AnalyzerException("Closure is not valid");
        }
        if ($functionCount > 1) {
            throw new AnalyzerException("Nested inline functions are not supported");
        }
        if ($returnCount < 1) {
            throw new AnalyzerException("Closure must have valid return statement");
        }
    }

    private function getReturnTokens($rawTokens)
    {
        $tokens = [];
        $afterFunction = false;
        $afterReturn = false;
        foreach ($rawTokens as $token) {
            $tokenType = is_array($token) ? $token[0] : null;

            if ($tokenType === T_COMMENT) { //ignore comments
                continue;
            }

            if ($tokenType === T_WHITESPACE) { //ignore white space
                continue;
            }

            if (!$afterFunction && $tokenType !== T_FUNCTION) { //ignore everything before function token
                continue;
            }

            if ($tokenType === T_FUNCTION) {
                $afterFunction = true;
                continue;
            }

            if (!$afterReturn && $tokenType !== T_RETURN) { //then ignore everything before return token
                continue;
            }

            if ($tokenType === T_RETURN) {
                $afterReturn = true;
                continue;
            }

            if ($token == ';') { //stop right before ;
                break;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }
}