# Админ-панель для phact

## Стандартный вывод модели в админ-панель

Допустим, выводим в админ-панель модель *Book*

Пример модели:

```php
class Book extends Model
{
    public static function getFields()
    {
        return [
            'name' => [
                'class' => CharField::class,
                'label' => 'Наименование'
            ],
        ];
    }
}
```

Добавляем в свой модуль папку *Admin*, создаем файл *BookAdmin.php*

*Именование файла не имеет ограничений, можно назвать его, к примеру, и BookMyAdmin.php*

```php
class BookAdmin extends Admin
{
    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getModel()
    {
        return new Book();
    }

    public static function getName()
    {
        return 'Книги';
    }

    public static function getItemName()
    {
        return 'Книга';
    }
}
```

Атрибуты и методы:

**getSearchColumns** - массив со списком атрбутов для поиска по моделям

**getModel** - модель, с которой работает данная "админка"

**getName** - именование "админки" (пункт в меню, хлебные крошки)

**getItemName** - именование отдельного элемента "админки"

## Подключение собственных форм

Установим свою форму для редактирования и создания моделей

Форма должна работать с моделью *Book* и быть унаследована от *ModelForm*

```php
class BookAdmin extends Admin
{
...
    public function getForm()
    {
        return new BookAdminForm();
    }
...
}
```

Если нам необходимо указать отдельные формы для редактирования и создания моделей,
указываем ее следующим образом:

```php
class BookAdmin extends Admin
{
...
    public function getForm()
    {
        return new BookAdminCreateForm();
    }

    public function getUpdateForm()
    {
        return new BookAdminUpdateForm();
    }
...
}
```

Атрибуты и методы:

**getForm** - указание формы для создания и редактирования моделей

**getUpdateForm** - указание отдельной формы для редактирования моделей

## Связанные админ-панели (RelatedAdmin)

Связанные админ-панели помогают организовать удобное создание и
редактирование моделей, связанных c текущей моделю через ForeignKey.
Например в нашем случае с книгами это будут ее издания (*Release*)

Пример модели *Release*:

```php
class Release extends Model
{
    public static function getFields()
    {
        return [
            'book' => [
                'class' => ForeignField::class,
                'modelClass' => Book::class,
                'label' => 'Книга'
            ],
            'year' => [
                'class' => CharField::class,
                'label' => 'Год'
            ],
            'position' => [
                'class' => PositionField::class,
                'editable' => false,
                'default' => 0,
                'relations' => [
                    'book'
                ]
            ]
        ];
    }

    public function __toString()
    {
        return (string) $this->year;
    }
}
```

Создаем для нее следующую "админку" *ReleaseAdmin*

```php
class ReleaseAdmin extends Admin
{
    public static $ownerAttribute = 'book';

    public function getSearchColumns()
    {
        return ['year'];
    }

    public function getModel()
    {
        return new Release();
    }

    public static function getName()
    {
        return 'Издания';
    }

    public static function getItemName()
    {
        return 'Издание';
    }
}
```

Как можно увидеть, от стандартной "админки" она отличается только
атрибутом **$ownerAttribute**, который указывает на связь в модели,
через которую данная "админка" будет поключатся к другим.

И модифицируем *BookAdmin*:

```php
class BookAdmin extends Admin
{
...
    public function getRelatedAdmins()
    {
        return [
            'releases' => ReleaseAdmin::class
        ];
    }
...
}
```

Теперь *ReleaseAdmin* будут выводится внутри *BookAdmin*

Атрибуты и методы:

**static $ownerAttribute** - указание связи для дочерних "админок",
через которую данная "админка" будет подключена к родительской

**getRelatedAdmins** - определение дочерних "админок" внутри родительской