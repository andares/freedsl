<?php
namespace FreeDSL;

/**
 * Description of Builder
 *
 * @author andares
 *
 */
class Builder {
    /**
     *
     */
    public $push_keys = [
        'must'      => 1,
        'should'    => 1,
        'must_not'  => 1,
    ];

    /**
     *
     * @var string
     */
    private $_index;

    /**
     *
     * @var string
     */
    private $_type;

    /**
     *
     * @var array
     */
    private $_root = [];

    /**
     *
     * @var array
     */
    private $_body = [];

    /**
     *
     * @var array
     */
    private $_params = [];

    /**
     *
     * @var &array
     */
    private $_cursor;

    /**
     *
     * @param string $index
     * @param string $type
     */
    public function __construct(string $index, string $type) {

        $this->_index   = $index;
        $this->_type    = $type;
    }

    /**
     *
     * @return array
     */
    public function __invoke(): array {
        $this->body();
        $dsl = array_merge_recursive([
            'index' => $this->_index,
            'type'  => $this->_type,
        ], $this->_root, ['body' => $this->_body]);
        return $dsl;
    }

    /**
     *
     * @param string $name
     * @param array $arguments
     * @return self
     */
    public function __call(string $name, array $arguments): self {
        if ($arguments) {
            if (count($arguments) > 1) {
                $this->_cursor[$name] = $arguments;
            } else {
                if (isset($this->push_keys[$name])) {
                    $this->_cursor[$name][] = $arguments[0];
                } else {
                    $this->_cursor[$name] = $arguments[0];
                }
            }
        } else {
            if (isset($this->push_keys[$name])) {
                $this->_cursor[$name][] = [];
            } else {
                $this->_cursor[$name] = [];
            }
        }
        if (isset($this->push_keys[$name])) {
            $this->_cursor = &$this->_cursor[$name][0];
        } else {
            $this->_cursor = &$this->_cursor[$name];
        }
        return $this;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name) {
        return $this->_root[$name] ?? null;
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value) {
        $this->_root[$name] = $value;
    }

    /**
     *
     * @param string $key
     */
    public function setPushKey(string $key) {
        $this->push_keys[$key] = 1;
    }

    /**
     *
     * @return self
     */
    public function body(): self {
        $this->_body    = array_merge_recursive($this->_body, $this->_params);
        $this->_params  = [];
        $this->_cursor  = &$this->_params;
        return $this;
    }

    /**
     *
     * @param string $name
     * @param type $arguments
     * @return self
     */
    public function named(string $name, ...$arguments): self {
        return $this->__call($name, $arguments);
    }

    /**
     *
     * @param string $yaml
     * @return self
     */
    public function yaml(string $yaml): self {
        $params = yaml_parse(trim($yaml));
        $params && $this->_cursor = $params;
        return $this->body();
    }
}
