<?php

trait PHPUnit_Contextual_Context
{
	/**
	* An array of callables invoked prior to thens being executed
	*/
	private $set_ups = [];

	/**
	* An associative array of names to callables invoked as test cases. Every parent set_up
	* is executed beforehand and every parent tear_down is invoked afterwards.
	*/
	private $thens = [];

	/**
	* An array of tear_down callables invoked after each then. 
	*/
	private $tear_downs = [];

	/**
	* The parent context, for which all the set_ups in that context are applied
	* to this one as well.
	*/
	protected $parent_context = null;

	/**
	* Children contexts of this context. All children mark this context as their parent.
	*/
	private $children_contexts = [];

	public function given($thing, Closure $execution)
	{
		$context = new Contextual_Description($thing, $this, $execution);
		$this->children_contexts[] = $context;
	}

	public function set_up($callable)
	{
		$this->set_ups[] = $callable;
	}

	public function tear_down($callable)
	{
		$this->tear_downs[] = $callable;
	}

	public function then($name, $callable)
	{
		$this->thens[$name] = $callable;
	}

	public function set_parent($parent)
	{
		$this->parent_context = $parent;
	}

	public function run(PHPUnit_Framework_TestResult $test_result = null)
	{

		foreach ($this->thens as $name => $then)
		{
			$test_result->run(new Contextual_TestCase_Invocation(function() use ($then){
				$this->invoke_set_ups();
				$then = $this->bind_to_root($then);
				$then();
				$this->invoke_tear_downs();
			}, $this->root_class_name() . "::given " . $this->context_name() . ", then $name"));
		}

		foreach ($this->children_contexts as $context)
		{
			$context->run($test_result);
		}
	}

	private function bind_to_root($fn)
	{
		return Closure::bind($fn, $this->root_object(), get_class($this->root_object()));
	}

	public function invoke_set_ups()
	{
		if ($this->parent_context)
		{
			$this->parent_context->invoke_set_ups();
		}
		foreach ($this->set_ups as $set_up)
		{
			$set_up = $this->bind_to_root($set_up);
			$set_up();
		}
	}

	public function invoke_tear_downs()
	{
		foreach ($this->tear_downs as $tear_down)
		{
			$tear_down = $this->bind_to_root($tear_down);
			$tear_down();
		}
		if ($this->parent_context)
		{
			$this->parent_context->invoke_tear_downs();
		}
	}

	public function count()
	{
		$count = count($this->thens);
		foreach ($this->children_contexts as $child)
		{
			$count += count($child);
		}
		return $count;
	}
}

interface Contextual_Test_Node
{
	public function context_name();

	public function root_class_name();

	public function root_object();
}

/**
* A description is a set of set_ups (states of the world) and
*/
class Contextual_Description implements Countable, Contextual_Test_Node
{
	use PHPUnit_Contextual_Context;

	public function __construct($thing, $parent, Closure $execution)
	{
		$this->thing = $thing;
		$this->set_parent($parent);
		$execution = Closure::bind($execution, $this);
		$execution();
	}

	public function context_name()
	{
		$name = $this->thing;
		$parent_context_name = $this->parent_context->context_name();
		if ($parent_context_name)
		{
			$name = "$parent_context_name and $name";
		}
		return $name;
	}

	public function root_class_name()
	{
		return $this->parent_context->root_class_name();
	}

	public function root_object()
	{
		return $this->parent_context->root_object();
	}
}

/**
* Wrapper for PHPUnit_Framework_TestResult which takes a test case and invokes
* runBare. This will delegate directly to a passed $test. To get the benefits
* of TestResult you need to provide more methods than are defined on PHPUnit_Framework_Test
*/
class Contextual_TestCase_Invocation extends PHPUnit_Framework_TestCase
{

	public function __construct($test, $name)
	{
		$this->test = $test;
		$this->setName($name);
	}

	public function run(PHPUnit_Framework_TestResult $test = null) {}

	public function count(){ return 1; }

	public function runBare()
	{
		$test = $this->test;
		$test();
	}

	public function getSize()
	{
		return PHPUnit_Util_Test::SMALL;
	}

}

abstract class PHPUnit_Contextual_TestCase implements PHPUnit_Framework_Test, Contextual_Test_Node
{
	use PHPUnit_Contextual_Context;

	public abstract function spec();

	public function __construct()
	{
		$this->load();
	}

	public function context_name()
	{
		return "";
	}

	public function root_class_name()
	{
		return get_class($this);
	}

	public function load()
	{
		if (empty($this->children_contexts))
		{
			$this->spec();
			if (empty($this->children_contexts))
			{
				throw new UnexpectedValueException("No descriptions were set_up in the test case");
			}
		}
	}

	public function root_object()
	{
		return $this;
	}
}

