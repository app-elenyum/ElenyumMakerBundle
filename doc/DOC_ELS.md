## Elenyum Specification Language (ESL)

ESL is at the core of the Elenyum Module Bundle. It defines how entities, relationships, and validation rules are structured in the system. Each entity's structure is outlined in a JSON specification, which serves as the blueprint for automatic API creation.

### Key Concepts

1. **Entities**: Define the core structure of your API's data model.
2. **Relationships**: Manage connections between entities.
3. **Validation**: Ensure data integrity through a variety of built-in validators.
4. **Fields**: Specify the type and properties of each field in your entities.

---

### Example of ESL JSON Structure

```json
{
  "entities": {
    "User": {
      "fields": {
        "id": {
          "type": "integer",
          "autoIncrement": true,
          "primaryKey": true
        },
        "username": {
          "type": "string",
          "length": 255,
          "validation": {
            "NotBlank": true,
            "Length": {
              "min": 4,
              "max": 255
            }
          }
        },
        "email": {
          "type": "string",
          "validation": {
            "Email": true
          }
        },
        "password": {
          "type": "string",
          "validation": {
            "NotCompromisedPassword": true
          }
        }
      },
      "relationships": {
        "Profile": {
          "type": "one-to-one",
          "targetEntity": "Profile",
          "mappedBy": "user",
          "cascade": ["persist", "remove"]
        }
      }
    }
  }
}
```

---

## Fields

Fields define the attributes of entities and come with a wide variety of types and options. Each field can have specific properties such as `type`, `length`, `nullable`, etc., and can be extended with validation rules.

### Common Field Types
- `string`: For text values.
- `integer`: For integer numbers.
- `float`: For floating-point numbers.
- `boolean`: For true/false values.
- `date`: For date-only values.
- `datetime`: For date and time.
- `time`: For time-only values.

### Example Field Definition

```json
{
  "fields": {
    "name": {
      "type": "string",
      "length": 255,
      "nullable": false,
      "validation": {
        "NotBlank": true
      }
    },
    "age": {
      "type": "integer",
      "validation": {
        "GreaterThanOrEqual": 18
      }
    }
  }
}
```

---

## Relationships

Entities can be related to each other in various ways, such as one-to-one, one-to-many, many-to-one, or many-to-many. ESL supports the definition of these relationships through JSON, allowing for flexible data modeling.

### Relationship Types

- **One-to-One**: A single entity is related to one other entity.
- **One-to-Many**: One entity is related to multiple other entities.
- **Many-to-One**: Multiple entities are related to a single entity.
- **Many-to-Many**: Multiple entities are related to multiple other entities.

### Example Relationship Definition

```json
{
  "relationships": {
    "Posts": {
      "type": "one-to-many",
      "targetEntity": "Post",
      "mappedBy": "author"
    }
  }
}
```

### Fields in Relationships

Each relationship requires a `targetEntity` to specify the related entity. Additional options like `mappedBy` and `inversedBy` define the owner of the relationship and its inverse side.

---

## Validators

Validation ensures that the data stored in your entities adheres to specific rules. The bundle provides a wide variety of built-in validators that can be applied dynamically. Each validator is defined within the ESL specification for fields and entities.

### List of Available Validators

The validators are categorized for ease of use:

#### Basic Validators
- `NotBlank`
- `Blank`
- `NotNull`
- `IsNull`
- `IsTrue`
- `IsFalse`
- `Type`

#### String Validators
- `Email`
- `Length`
- `Url`
- `Regex`
- `Hostname`
- `Ip`
- `Cidr`
- `Json`
- `Uuid`
- `Ulid`
- `UserPassword`
- `NotCompromisedPassword`
- `PasswordStrength`
- `CssColor`
- `NoSuspiciousCharacters`

#### Comparison Validators
- `EqualTo`
- `NotEqualTo`
- `IdenticalTo`
- `NotIdenticalTo`
- `LessThan`
- `LessThanOrEqual`
- `GreaterThan`
- `GreaterThanOrEqual`
- `Range`
- `DivisibleBy`
- `Unique`

#### Number Validators
- `Positive`
- `PositiveOrZero`
- `Negative`
- `NegativeOrZero`

#### Date Validators
- `Date`
- `DateTime`
- `Time`
- `Timezone`

#### Choice Validators
- `Choice`
- `Language`
- `Locale`
- `Country`

#### File Validators
- `File`
- `Image`

#### Financial Validators
- `Bic`
- `CardScheme`
- `Currency`
- `Luhn`
- `Iban`
- `Isbn`
- `Issn`
- `Isin`

### Example Validator Usage

```json
{
  "fields": {
    "username": {
      "type": "string",
      "validation": {
        "NotBlank": true,
        "Length": {
          "min": 4,
          "max": 20
        }
      }
    }
  }
}
```

---

## Conclusion

The Elenyum Module Bundle provides developers with a powerful and flexible way to build APIs by defining data modESL, relationships, and validation rules through the Elenyum Specification Language (ESL). By leveraging a simple JSON structure, developers can create fully functional APIs with minimal configuration, focusing more on the business logic and less on the underlying technical complexity.
