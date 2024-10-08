# Install

```
composer require akbarali/action-data
```

# Instructions for use

Laravel Request Used:

```php
StoreIncomeActionData::createFromRequest($request);
```

Array Used:

```php
StoreIncomeActionData::createFromArray($array);
```

Json Used:

```php
StoreIncomeActionData::createFromJson($json);
```

If you want to add a new validation type between functions

```php
$actionData->addValidationRule('id', 'required|integer');
//To check again
$actionData->validateException();
```

Let's say you have data in array type. You can also add value to it yourself. For example:

```php
$userId = Auth::id();
$actionData->set('data', '*.user_id', $userId);
$actionData->addValidationRule('data.*.user_id', 'required|integer');
$actionData->validateException();
```

ActionData get Array

```php
$actionData->toArray();
// or  
$actionData->all();
// or
$actionData->toSnakeArray();
```

If you only need some of that class

```php
$actionData->only(['id', 'name']);
```

If you want direct access in Laravel Controller, add it to `providers` in `/config/app.php`

```php
'providers' => [
...
\Akbarali\ActionData\Providers\ActionDataServiceProvider::class,
]
```

Then you can use the ActionData class in the Controller function
Example:

```php
public function store(StoreIncomeActionData $actionData)
{
//This includes validated information. Otherwise, it returns an ActionDataException.
}
```

# 0.8 version

Add `isUpdate()` method

```php
if($actionData->isUpdate()){
// Update
}else{
// Create
}
```

# 1.0.0 version

Added `setUser` `isCreate` `getUser` `updated` `created` `fromRequest` and `fromArray` methods

`setUser` method is used to set

```php
class StorePotentialPartnersActionData extends \Akbarali\ActionData\ActionDataBase
{
    public ?int    $id;
    public ?int    $agent_id;
    public ?string $phone;

    public ?string $full_name;
    public ?string $address;
    public ?string $description;
    /** @var array<UploadedFile>|null */
    public ?array $images    = [];
    public ?array $oldImages = [];

    protected function prepare(): void
    {
        $this->rules = [
            'id'          => 'nullable|numeric|exists:potential_partner,id',
            'full_name'   => 'required',
            'address'     => 'required',
            'description' => 'nullable',
            "images"      => "required_without:oldImages|array",
            "images.*"    => "required|image",
            "oldImages"   => "required_without:images|array",
            "oldImages.*" => "required|int|exists:potential_partner_images,id",
        ];
    }

    protected function setUser(): void
    {
        $this->user = auth()->user();
    }
}
```

`updated` method means that it is an update operation.

```php
/**
 * @param int     $id
 * @param Request $request
 * @return RedirectResponse
 * @throws ValidationException
 */
public function update(int $id, Request $request): RedirectResponse
{
   try {
        $request->request->set('id', $id);
        $this->storePotentialPartner(StorePotentialPartnersActionData::fromRequest($request)->updated());
        return to_route('page.index')->with('message', trans('all.updated'));
    } catch (OperationException|ActionDataException|QueryException $e) {
        return back()->withInput()->withErrors($e->getMessage());
   }
}
```

If you do not call `->updated()` after `fromRequest`, it will automatically check by id.
If the id is in your request, then it knows that it is an update operation

That's how it's written:

```php
public function isUpdate(): bool
{
    return $this->updated ?? (isset($this->id) && $this->id > 0);
}
```

`created()` is the opposite of `->updated()`

`fromRequest` to reference `createFromRequest` method
`fromArray` to reference `createFromArray` method

# 1.0.1 version

readonly supported

```php
class StorePartnersActionData extends \Akbarali\ActionData\ActionDataBase
{
    public readonly int $id;

    protected function prepare(): void
    {
        $this->rules = [
            'id'          => 'nullable|numeric|exists:potential_partner,id',
        ];
    }
}

class PartnerController extends Controller{

    public function update(int $id, Request $request): RedirectResponse
    {
       try {
            $request->request->set('id', $id);
            $this->storePartner(StorePartnersActionData::fromRequest($request)->updated());
            return to_route('page.index')->with('message', trans('all.updated'));
        } catch (OperationException|ActionDataException|QueryException $e) {
            return back()->withInput()->withErrors($e->getMessage());
       }
    }
    
    public function storePartner(StorePotentialPartnersActionData $actionData): RedirectResponse
    {
        $actionData->id = 1; // Exception: Error
    }
}

```

Error: Cannot modify readonly property `App\ActionData\StorePartnersActionData::$id`

# 1.2.3 version

```php
$actionData->addValidationRule('pinfl', 'unique:users,pinfl');
$actionData->addValidationRules([
	'pinfl' => 'unique:users,pinfl',
	'phone' => 'unique:users,phone',
	'passport' => 'unique:users,passport',
]);
$actionData->addValidationValue('required', [
	'pinfl', 'passport', 'phone', 'passportIssueDate',
	'firstName', 'lastName', 'middleName', 'docType',
	'card',
]);
```

# 1.4.0 version

Added `fromJson` and bug fix `createFromArray`

