<?php

namespace Akbarali\ActionData;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

abstract class ActionDataBase implements ActionDataContract
{
	private \Illuminate\Contracts\Validation\Validator $validator;
	protected array                                    $rules = [];
	protected mixed                                    $user  = null;
	protected bool                                     $updated;
	
	protected function prepare(): void {}
	
	/**
	 * @param  array  $parameters
	 * @throws ActionDataException
	 * @return self
	 * @deprecated 2.1 Use fromArray() instead
	 * @see        fromArray()
	 */
	public static function createFromArray(array $parameters = []): static
	{
		$instance = new static;
		try {
			/** @var array<string, \ReflectionProperty> $fields */
			$fields = DOCache::resolve(static::class, static function () {
				$class  = new \ReflectionClass(static::class);
				$fields = [];
				foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $reflectionProperty) {
					if ($reflectionProperty->isStatic()) {
						continue;
					}
					$field          = $reflectionProperty->getName();
					$fields[$field] = $reflectionProperty;
				}
				
				return $fields;
			});
			
			foreach ($fields as $field => $validator) {
				$value = ($parameters[$field] ?? $parameters[Str::snake($field)] ?? $validator->getDefaultValue() ?? $instance->{$field} ?? null);
				if (is_null($value) && $validator->getType()?->allowsNull() === false) {
					throw new ActionDataException("Field {$field} is required", ActionDataException::ERROR_NOT_NULLABLE);
				}
				$validator->setAccessible(true);
				$validator->setValue($instance, $value);
			}
		} catch (\Throwable $exception) {
			if ($exception instanceof ActionDataException) {
				throw $exception;
			}
		}
		
		if (method_exists($instance, 'setUser')) {
			$instance->setUser();
		}
		
		$instance->prepare();
		
		return $instance;
	}
	
	/**
	 * @param  array  $parameters
	 * @throws ActionDataException
	 * @throws ValidationException
	 * @return static
	 */
	public static function fromArray(array $parameters = []): static
	{
		$res = static::createFromArray($parameters);
		$res->validate(false);
		
		return $res;
	}
	
	/**
	 * @param  Request  $request
	 * @throws ActionDataException
	 * @throws ValidationException
	 * @return self
	 */
	public static function createFromRequest(Request $request): static
	{
		$res = static::createFromArray($request->all());
		$res->validate(false);
		
		return $res;
	}
	
	/**
	 * @throws ValidationException
	 * @throws ActionDataException
	 */
	public static function fromRequest(Request $request): static
	{
		return static::createFromRequest($request);
	}
	
	/**
	 * @param  string  $json
	 * @throws ActionDataException
	 * @throws \JsonException
	 * @return self
	 */
	public static function createFromJson(string $json): static
	{
		return static::createFromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
	}
	
	public function addValidationRule($name, $value): void
	{
		$this->rules[$name] = $value;
	}
	
	public function addValidationRules(array $rules): void
	{
		foreach ($rules as $name => $value) {
			$this->addValidationRule($name, $value);
		}
	}
	
	public function addValidationValue(string $name, array $fields): void
	{
		foreach ($fields as $field) {
			$this->addValidationRule($field, $name);
		}
	}
	
	/**
	 * @param  bool  $silent
	 * @throws ValidationException
	 * @return bool
	 */
	public function validate(bool $silent = true): bool
	{
		$this->validator = Validator::make($this->toArray(true), $this->getValidationRules(), $this->getValidationMessages(), $this->getValidationAttributes());
		if ($silent) {
			return !$this->validator->fails();
		}
		$this->validator->validate();
		
		return true;
	}
	
	/**
	 * @param  bool  $silent
	 * @throws ValidationException
	 * @return void
	 */
	public function validateException(bool $silent = true): void
	{
		if (!$this->validate()) {
			throw ValidationException::withMessages($this->getValidationErrors()?->toArray());
		}
		
		if (count($this->toArray(true)) === 0) {
			throw ValidationException::withMessages(['action_data_empty' => 'Action data is empty']);
		}
	}
	
	/**
	 * @throws ValidationException
	 */
	public function validated(): array
	{
		return $this->validator->validated();
	}
	
	public function getValidationErrors(): ?MessageBag
	{
		return $this->validator->errors();
	}
	
	protected function getValidationMessages(): array
	{
		if (file_exists(resource_path('lang/'.app()->getLocale().'/validation.php')) || file_exists(base_path('lang/'.app()->getLocale().'/validation.php'))) {
			$validation = trans('validation');
		} else {
			$validation = [];
		}
		
		if (method_exists($this, 'messages') && count($this->messages()) > 0) {
			$validation = array_merge($validation, $this->messages());
		}
		
		return $validation;
	}
	
	
	protected function getValidationAttributes(): array
	{
		if (file_exists(resource_path('lang/'.app()->getLocale().'/validation.php'))) {
			return trans('form');
		}
		
		return [];
	}
	
	/**
	 * @param  bool  $trimNulls
	 * @return array
	 */
	public function toArray(bool $trimNulls = false): array
	{
		$data = [];
		
		try {
			$class      = new \ReflectionClass(static::class);
			$properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
			foreach ($properties as $reflectionProperty) {
				if ($reflectionProperty->isStatic()) {
					continue;
				}
				$value = $reflectionProperty->getValue($this);
				if ($trimNulls === true) {
					if (!is_null($value)) {
						$data[$reflectionProperty->getName()] = $value;
					}
				} else {
					$data[$reflectionProperty->getName()] = $value;
				}
			}
		} catch (\Exception $exception) {
		
		}
		
		return $data;
	}
	
	public function all(bool $trimNulls = false): array
	{
		return $this->toArray($trimNulls);
	}
	
	/**
	 * @param  bool  $trimNulls
	 * @return array
	 */
	public function toSnakeArray(bool $trimNulls = false): array
	{
		$data = [];
		try {
			$class      = new \ReflectionClass(static::class);
			$properties = $class->getProperties(\ReflectionProperty::IS_PUBLIC);
			foreach ($properties as $reflectionProperty) {
				if ($reflectionProperty->isStatic()) {
					continue;
				}
				$value = $reflectionProperty->getValue($this);
				if ($trimNulls === true) {
					if (!is_null($value)) {
						$data[Str::snake($reflectionProperty->getName())] = $value;
					}
				} else {
					$data[Str::snake($reflectionProperty->getName())] = $value;
				}
			}
		} catch (\Exception $exception) {
		}
		
		return $data;
	}
	
	/**
	 * @param  bool  $trimNulls
	 * @param  int   $options
	 * @throws \JsonException
	 * @return string
	 */
	public function toJson(bool $trimNulls = false, int $options = 0): string
	{
		return json_encode($this->toArray($trimNulls), $options | JSON_THROW_ON_ERROR);
	}
	
	public function getOnly(array $finding = []): Collection
	{
		return collect($this->toArray())->only($finding);
	}
	
	/**
	 * @param  string  $property
	 * @param  string  $key
	 * @param  mixed   $value
	 * @throws ActionDataException
	 * @return $this
	 */
	public function set(string $property, string $key, mixed $value): self
	{
		if (!property_exists($this, $property)) {
			throw new ActionDataException("Property {$property} not exists in ".static::class);
		}
		
		if (!is_array($this->{$property})) {
			throw new ActionDataException("Property {$property} not array in ".static::class);
		}
		
		data_set($this->{$property}, $key, $value);
		
		return $this;
	}
	
	/**
	 * @param  string  $property
	 * @param  string  $key
	 * @throws ActionDataException
	 * @return $this
	 */
	public function forget(string $property, string $key): self
	{
		if (!property_exists($this, $property)) {
			throw new ActionDataException("Property {$property} not exists in ".static::class);
		}
		
		if (!is_array($this->{$property})) {
			throw new ActionDataException("Property {$property} not array in ".static::class);
		}
		
		data_forget($this->{$property}, $key);
		
		return $this;
	}
	
	/**
	 * @param  string  $property
	 * @param  string  $key
	 * @throws ActionDataException
	 * @return mixed
	 */
	public function get(string $property, string $key = ''): mixed
	{
		if (!property_exists($this, $property)) {
			throw new ActionDataException("Property {$property} not exists in ".static::class);
		}
		
		if (!is_array($this->{$property})) {
			throw new ActionDataException("Property {$property} not array in ".static::class);
		}
		
		return data_get($this->{$property}, $key);
	}
	
	private function getValidationRules(): array
	{
		return $this->rules;
	}
	
	public function isUpdate(): bool
	{
		return $this->updated ?? (isset($this->id) && $this->id > 0);
	}
	
	public function isCreate(): bool
	{
		return !$this->isUpdate();
	}
	
	public function getUser(): mixed
	{
		return $this->user;
	}
	
	public function updated(): static
	{
		$this->updated = true;
		
		return $this;
	}
	
	public function created(): static
	{
		$this->updated = false;
		
		return $this;
	}
	
	/**
	 * @param  string  $property
	 * @throws ActionDataException
	 * @return bool
	 */
	public function has(string $property): bool
	{
		if (!property_exists($this, $property)) {
			throw new ActionDataException("Property {$property} not exists in ".static::class);
		}
		
		return isset($this->{$property});
	}
	
}
