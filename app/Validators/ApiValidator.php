<?php

namespace App\Validators;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ApiValidator
{
    /**
     * Request
     *
     * @var Request
     */
    protected Request $request;

    /**
     * Validation rules
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Custom validation messages
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Custom validation attributes
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Validate
     *
     * @return array
     * @throws ValidationException
     */
    public function validate(): array
    {
        try {
            $validator = Validator::make(
                $this->request->all(),
                $this->rules,
                $this->messages,
                $this->attributes
            );

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            return $validator->validated();
        } catch (\Exception $e) {
            Log::error('API validation failed', [
                'rules' => $this->rules,
                'messages' => $this->messages,
                'attributes' => $this->attributes,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get validation rules
     *
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * Set validation rules
     *
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /**
     * Get custom validation messages
     *
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Set custom validation messages
     *
     * @param array $messages
     * @return void
     */
    public function setMessages(array $messages): void
    {
        $this->messages = $messages;
    }

    /**
     * Get custom validation attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set custom validation attributes
     *
     * @param array $attributes
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Get request
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Set request
     *
     * @param Request $request
     * @return void
     */
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    /**
     * Add validation rule
     *
     * @param string $field
     * @param string|array $rule
     * @return void
     */
    public function addRule(string $field, $rule): void
    {
        if (!isset($this->rules[$field])) {
            $this->rules[$field] = [];
        }

        if (is_array($rule)) {
            $this->rules[$field] = array_merge($this->rules[$field], $rule);
        } else {
            $this->rules[$field][] = $rule;
        }
    }

    /**
     * Remove validation rule
     *
     * @param string $field
     * @param string|array $rule
     * @return void
     */
    public function removeRule(string $field, $rule): void
    {
        if (!isset($this->rules[$field])) {
            return;
        }

        if (is_array($rule)) {
            $this->rules[$field] = array_diff($this->rules[$field], $rule);
        } else {
            $this->rules[$field] = array_diff($this->rules[$field], [$rule]);
        }
    }

    /**
     * Add custom validation message
     *
     * @param string $field
     * @param string $rule
     * @param string $message
     * @return void
     */
    public function addMessage(string $field, string $rule, string $message): void
    {
        $this->messages[$field . '.' . $rule] = $message;
    }

    /**
     * Add custom validation attribute
     *
     * @param string $field
     * @param string $attribute
     * @return void
     */
    public function addAttribute(string $field, string $attribute): void
    {
        $this->attributes[$field] = $attribute;
    }

    /**
     * Get validation error response
     *
     * @param ValidationException $e
     * @return \Illuminate\Http\JsonResponse
     */
    public function getErrorResponse(ValidationException $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    }
} 