# Install

```
composer require akbarali/action-data
```

# Ishlatish bo'yicha yo'riqnoma

Laravel Requestdan olish uchun:

```php
StoreIncomeActionData::createFromRequest($request);
```

Arraydan olish uchun

```php
StoreIncomeActionData::createFromArray($array);
```

Jsondan olish

```php
StoreIncomeActionData::createFromJson($json);
```

Agar siz yarim yo'lda validatsiya qo'shmoqchi bo'lsangiz:

```php
$actionData->addValidationRule('id', 'required|integer');
//Qayta yuklash uchun
$actionData->validateException();
```

Aytaylik sizda data degan narsa array tipida. Siz unga o'zingiz qiymat qo'shishingiz ham mumkin. Masalan:

```php
$userId = Auth::id();
$actionData->set('data', '*.user_id', $userId);
$actionData->addValidationRule('data.*.user_id', 'required|integer');
$actionData->validateException();
```

ActionData ni arrayga o'girish uchun:

```php
$actionData->toArray();
// or  
$actionData->all();
// or
$actionData->toSnakeArray();
```

Siza o'sha classdan faqat bazilari kerak bo'lsa

```php
$actionData->only(['id', 'name']);
```

Agar Laravel Controllerda Pramoy kirishini hohlayman desangiz `/config/app.php` ning `providers` siga qo'shib qo'ying

```php
'providers' => [
...
\Akbarali\ActionData\Providers\ActionDataServiceProvider::class,
]
```

Shunda siz Controllerni funksiyasida ActionData classini ishlatishingiz mumkin

```php
public function store(StoreIncomeActionData $actionData)
{
//Bu yerga validatsiyadan o'tgan ma'lumot kiradi. Bo'lmasa exception qaytaradi
}
```

# 0.8 version add isUpdate() method

```php
if($actionData->isUpdate()){
// Update
}else{
// Create
}
```

# 1.0.0 version added `setUser` `isCreate` `getUser` `updated` `created` and `fromRequest` methods

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
        return to_route('admin.potential_partner.index')->with('message', trans('all.updated'));
    } catch (OperationException|ActionDataException|QueryException $e) {
        return back()->withInput()->withErrors($e->getMessage());
   }
}
```

If you do not call `->updated()` after `fromRequest`, it will automatically check by id.
If the id is in your request, then it knows that it is an update operation
That is

```php
public function isUpdate(): bool
{
    return $this->updated ?? (isset($this->id) && $this->id > 0);
}
```

`created()` is the opposite of `->updated()`

`fromRequest` to reference `createFromRequest` method
