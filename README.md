[![IPS-Version](https://img.shields.io/badge/Symcon_Version-6.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

Umwandeln von Text in Markdown-Syntax zu HTML.

## 2. Voraussetzungen

- IP-Symcon ab Version 6.0

## 3. Installation

### a. Installation des Moduls

Im [Module Store](https://www.symcon.de/service/dokumentation/komponenten/verwaltungskonsole/module-store/) ist das Modul unter dem Suchbegriff *Markdown* zu finden.<br>
Alternativ kann das Modul über [Module Control](https://www.symcon.de/service/dokumentation/modulreferenz/module-control/) unter Angabe der URL
`https://github.com/demel42/IPSymconMarkdown.git` installiert werden.

### b. Einrichtung in IPS

_Instanz hinzufügen_ anwählen und als Hersteller _(sonstiges)_ sowie als Gerät _Markdown_ auswählen. Dien entstehende Instanz hat keine Eigenschaften.

## 4. Funktionsreferenz

`string Markdown_Convert2HTML(integer $InstanzID, string $markdown, array $opts)`<br>
Wandelt den übergebenen Markdown-codierten Text in HTML um.<br>
Als EInstellungen stehen in _opts_ zur Verfügung:

| Option        | Typ     | Beschreibung |
| :------------ | :------ | :----------- |
| Inline        | boolean | |
| SafeMode      | boolean | |
| MarkupEscaped | boolean | |
| BreaksEnabled | boolean | |
| UrlsLinked    | boolean | |

Erklärung der Optionen siehe in [Github](https://github.com/erusev/parsedown#readme) bzw. im [Tutorial](https://github.com/erusev/parsedown/wiki/Tutorial:-Get-Started).

## 5. Konfiguration

### Markdown

#### Properties

Die Instanz hat keine Eigenschaften.

#### Aktionen

Die Instanz stellt keine Aktionen zur Verfügung.

### Variablenprofile

Die Instanz erstellt keine eigenen Variablenprofile.

## 6. Anhang

### GUIDs
- Modul: `{44955850-8F58-61DA-E22F-C0E11DC348EA}`
- Instanzen:
  - Markdown: `{DDC7D576-84FF-EA16-EDC9-1D27C495B806}`
- Nachrichten:

### Quellen
- [Parsedown - Better Markdown Parser in PHP](https://github.com/erusev/parsedown.git)
- ein Javascrpt basierter Editor ist der [SimpleMDE](https://github.com/sparksuite/simplemde-markdown-editor)

## 7. Versions-Historie

- 1.0 @ 19.10.2022 16:48
  - Initiale Version
