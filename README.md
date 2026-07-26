# Platform Sandbox

Sandbox ist die **Spielwiese des autonomen Workers**. Das Modul ist ein leeres,
aber lauffähiges Modul-Grundgerüst zum Experimentieren, Ausprobieren von Mustern
und Testen von Änderungen — bewusst **nicht** für Produktiv-Features gedacht.

Aufbau und Konventionen folgen dem `module-template`
(siehe `platform/modules/module-template`): ServiceProvider mit Registrierung bei
`PlatformCore`, Config unter `config/sandbox.php`, Route auf das Dashboard und
Livewire-Komponenten in `src/Livewire`.
