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
...
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

