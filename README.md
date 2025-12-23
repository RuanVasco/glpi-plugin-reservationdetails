# GLPI Plugin - Reservation Details

Plugin for GLPI that adds contextual fields to the asset reservation form, including resource management with stock control and profile-based reservation permissions.

## Requirements

- GLPI version 11.0.4 to 11.0.5
- PHP 8.1 or higher
- MySQL/MariaDB

## Features

### Resource Management
- Create and manage resources (coffee, water, projectors, etc.)
- Stock control per resource with availability checking
- Link resources to reservation items (rooms, equipment)
- Automatic unavailability detection when stock is exhausted
- Automatic ticket creation when resources are requested

### Custom Fields
- Create custom fields for reservations
- Multiple field types: text, textarea, dropdown, checkbox, date
- Fields are displayed in the reservation form

### Reservation Permissions by Asset Type
- Restrict which profiles can reserve specific asset types
- Configuration via Setup > Dropdowns > Reservation Details > Permissões de Reserva
- Items with restrictions are hidden from unauthorized users
- Empty restrictions = all profiles can reserve

### Reservations View Tab
- View all reservations with associated resources
- Filter between open and closed reservations
- Search by item name, user, or date
- Modal with reservation details including user, item, dates, and resources

### Permission System
- Granular permissions per profile
- Resource management rights: Read, Create, Update, Delete, Purge
- Custom Fields rights: Read, Create, Update, Delete, Purge
- Reservation view rights: Read, Create
- Configure in Administration > Profiles > Reservation Details tab

## Installation

1. Download the plugin and extract to `plugins/reservationdetails/`
2. Go to Setup > Plugins
3. Install and activate the plugin
4. Configure permissions in Administration > Profiles

## Configuration

### Creating Resources

1. Go to Setup > Dropdowns > Resources
2. Click Add
3. Fill in the resource name
4. Set stock quantity (optional - leave empty for unlimited)
5. Select which reservation items can use this resource
6. Optionally configure ticket creation for resource requests

### Creating Custom Fields

1. Go to Setup > Dropdowns > Custom Fields
2. Click Add
3. Set field name, type, and options
4. Fields will appear in the reservation form

### Configuring Reservation Permissions

1. Go to Setup > Dropdowns > Reservation Details > Permissões de Reserva
2. You'll see all reservable asset types
3. Click "Editar" to configure which profiles can reserve each type
4. Select the allowed profiles (leave empty for all)
5. Click "Salvar"

### Configuring Profile Permissions

1. Go to Administration > Profiles
2. Select the desired profile
3. Click on the "Reservation Details" tab
4. Configure rights for Resources, Custom Fields, and Reservations

### Permission Levels

| Profile Type | Suggested Permissions |
|--------------|----------------------|
| Super-Admin  | Full access to all features |
| Reception    | Read, Create, Update resources; Read reservations |
| Self-Service | No resource management; Can only reserve |

## File Structure

```
reservationdetails/
├── ajax/
│   ├── entities.php
│   └── reservations.php
├── front/
│   ├── customfield.form.php
│   ├── customfield.php
│   ├── itemtype_permissions.php
│   ├── reservation.form.php
│   ├── reservation.php
│   ├── reservations.php
│   ├── resource.form.php
│   └── resource.php
├── src/
│   ├── Entity/
│   │   ├── CustomField.php
│   │   ├── ItemPermission.php
│   │   ├── Profile.php
│   │   ├── Reservation.php
│   │   ├── ReservationView.php
│   │   └── Resource.php
│   ├── Repository/
│   │   ├── ReservationRepository.php
│   │   └── ResourceRepository.php
│   └── Utils.php
├── templates/
│   ├── form_recourse_after_add.html.twig
│   ├── reservations_list.html.twig
│   └── resource_form.html.twig
├── hook.php
└── setup.php
```

## Database Tables

| Table | Description |
|-------|-------------|
| glpi_plugin_reservationdetails_resources | Resources (coffee, projectors, etc.) |
| glpi_plugin_reservationdetails_resources_reservationsitems | Link resources to reservation items + tracks reservations and tickets |
| glpi_plugin_reservationdetails_customfields | Custom field definitions |
| glpi_plugin_reservationdetails_customfields_values | Custom field values per reservation |
| glpi_plugin_reservationdetails_itemtypes_profiles | Profile permissions per asset type |

## Usage

### Making a Reservation with Resources

1. Create a reservation in GLPI as usual
2. After saving, the plugin form appears
3. Select the desired resources (unavailable ones are disabled)
4. Click Add

### Viewing Reservations

1. Go to Tools > Reservations
2. Click on the "Visualizar Reservas" tab
3. Use the dropdown to filter between open and closed reservations
4. Use the search box to find specific reservations
5. Click the eye icon to view reservation details

### Configuring Asset Type Restrictions

1. Go to Setup > Dropdowns > Reservation Details > Permissões de Reserva
2. Click "Editar" on the asset type you want to restrict
3. Select which profiles can reserve this type
4. Save - restricted users won't see these items

## License

GPLv3+

## Author

Ruan Vasconcelos - https://github.com/RuanVasco
