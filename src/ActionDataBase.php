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
     * @param array $parameters
     * @return self
     * @throws ActionDataException
     */
    public static function createFromArray(array $parameters = []): static
    {
        $instance = new static;
        try {
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
                $instance->{$field} = $value;
                unset($parameters[$field]);
            }
        } catch (\Throwable $exception) {
            if ($exception instanceof ActionDataException) {
                throw $exception;
            }
        }

        $instance->prepare();

        if (method_exists($instance, 'setUser')) {
            $instance->setUser();
        }

        return $instance;
    }

    /**
     * @param Request $request
     * @return self
     * @throws ActionDataException
     * @throws ValidationException
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
     * @param string $json
     * @return self
     * @throws ActionDataException
     * @throws \JsonException
     */
    public static function createFromJson(string $json): static
    {
        return static::createFromArray(json_decode($json, true, 512, JSON_THROW_ON_ERROR));
    }

    public function addValidationRule($name, $value): void
    {
        $this->rules[$name] = $value;
    }

    /**
     * @param bool $silent
     * @return bool
     * @throws ValidationException
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
     * @param bool $silent
     * @return void
     * @throws ValidationException
     */
    public function validateException(bool $silent = true): void
    {
        if (!$this->validate()) {
            throw ValidationException::withMessages($this->getValidationErrors()->toArray());
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
        $validation = trans('validation');
        if (method_exists($this, 'messages') && count($this->messages()) > 0) {
            $validation = array_merge($validation, $this->messages());
        }

        return $validation;
    }

    protected function getValidationAttributes(): array
    {
        return trans('form');
    }

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toArray(bool $trim_nulls = false): array
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
                if ($trim_nulls === true) {
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

    public function all(bool $trim_nulls = false): array
    {
        return $this->toArray($trim_nulls);
    }

    /**
     * @param bool $trim_nulls
     * @return array
     */
    public function toSnakeArray(bool $trim_nulls = false): array
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
                if ($trim_nulls === true) {
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

    public function getOnly(array $finding = []): Collection
    {
        return collect($this->toArray())->only($finding);
    }

    /**
     * @param string $property
     * @param string $key
     * @param mixed  $value
     * @return $this
     */
    public function set(string $property, string $key, mixed $value): self
    {
        if (!property_exists($this, $property)) {
            throw new \RuntimeException("Property {$property} not exists in ".static::class);
        }

        if (!is_array($this->{$property})) {
            throw new \RuntimeException("Property {$property} not array in ".static::class);
        }

        data_set($this->{$property}, $key, $value);

        return $this;
    }

    /**
     * @param string $property
     * @param string $key
     * @return $this
     */
    public function forget(string $property, string $key): self
    {
        if (!property_exists($this, $property)) {
            throw new \RuntimeException("Property {$property} not exists in ".static::class);
        }

        if (!is_array($this->{$property})) {
            throw new \RuntimeException("Property {$property} not array in ".static::class);
        }

        data_forget($this->{$property}, $key);

        return $this;
    }

    /**
     * @param string $property
     * @param string $key
     * @return mixed
     */
    public function get(string $property, string $key = ''): mixed
    {
        if (!property_exists($this, $property)) {
            throw new \RuntimeException("Property {$property} not exists in ".static::class);
        }

        if (!is_array($this->{$property})) {
            throw new \RuntimeException("Property {$property} not array in ".static::class);
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

}
