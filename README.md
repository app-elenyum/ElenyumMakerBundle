
# ElenyumMakerBundle

**ElenyumMakerBundle** is a tool for generating Symfony modules based on Elenyum specifications (ESL). The bundle allows you to create controllers, entities, services, and repositories from a pre-defined JSON file.

---

## Installation
```bash
composer require elenyum/maker
```

## Configuration

### 1. Add the Route
Manually add the route for module generation in `config/routes/elenyum_maker.yaml`:

```yaml
app.maker:
  path: /elenyum/dash/maker
  methods:
    - POST
    - GET
  defaults: { _controller: elenyum_maker }
```

### 2. Configure Security
Add access control rules in `config/packages/security.yaml`:

```yaml
security:
  access_control:
    - { path: ^/elenyum/dash/maker, roles: ROLE_ADMIN }
```

### 3. Bundle Configuration
Create a configuration file `config/packages/elenyum_maker.yaml`:

```yaml
elenyum_maker:
  cache:
    enable: false
    item_id: 'elenyum_maker'
  root:
    path: '%kernel.project_dir%/module'
    namespace: 'Module'
    prefix: 'Module'
  doctrine:
    names:
      - 'ElenyumAuthorizationBundle'
  securityName: 'api_key'
```

---

## Usage

### Console Command
To create a module, use the following console command:
```bash
php bin/console elenyum:make -f path/to/els.json
```
- **-f, --file**: Path to the ESL specification JSON file.

### Aliases
Alias for the command:
```bash
php bin/console e:m -f path/to/els.json
```

---

## Generated Files
When generating a module, the following structure is created:

```
/module/{ModuleName}/v1/
├── Controller
│   ├── {Entity}GetController.php
│   ├── {Entity}ListController.php
│   ├── {Entity}PostController.php
│   ├── {Entity}PutController.php
│   └── {Entity}DeleteController.php
├── Entity
│   └── {Entity}.php
├── Service
│   └── {Entity}Service.php
└── Repository
    └── {Entity}Repository.php
```

- **Controllers** are created for `GET`, `POST`, `PUT`, `DELETE` methods.
- **Services and repositories** are generated for each entity.

---

## OpenAPI Documentation

OpenAPI documentation is generated using the `securityName` parameter from `elenyum_maker.yaml`. It is applied in the controller attributes as shown:

```php
#[Auth(name: 'Bearer', model: News::class)]
```

If no authorization is required, the parameter can be left empty.

---

## Example of Full Route Configuration

```yaml
app.maker:
  path: /elenyum/dash/maker
  methods:
    - POST
    - GET
  defaults: { _controller: elenyum_maker }
```

---

## Notes
- Configuration and route files are created manually.
- Doctrine configuration is set in `elenyum_maker.yaml`.
- The path for module generation: `%kernel.project_dir%/module`.
