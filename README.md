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

Das Modul stellt eine Funktion zur Verfügung, um Texte mit Markdown in HTML umzuwandeln.
Zusätzlich gibt es die Möglichkeit, über einen externen WYSIWYG-Editor solche Texte zu erfassen.

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
Als Einstellungen stehen in _opts_ zur Verfügung, Beschreibung siehe _Properties_ -> _Standardwerte_

## 5. Konfiguration

### Markdown

#### Properties

| Eigenschaft                | Typ     | Standardwert   | Beschreibung |
| :------------------------- | :------ | :------------- | :----------- |
| Instanz deaktivieren       | boolean | false          | Instanz temporär deaktivieren |
|                            |         |                | |
| Zugriff zum Webhook        |         |                | |
| ... Identifikation         | string  | /hook/Markdown | muss geändert werden, wenn es mehr als eine Instanz gibt |
| ... Benutzerkennung        | string  |                | optionale Benutzerkennung zur Authentifizierung |
| ... Passwort               | string  |                | optionales Passwort zur Authentifizierung |
|                            |         |                | |
| Einträge                   | array   |                | Defintion zuässiger Variablen |
| ... Markdown-Variable      | integer | 0              | Variable mit dem Markdown-Code _[1]_ |
| ... HTML-Variable          | integer | 0              | Variable mit dem erzeugten HTML-Code _[2]_ |
| ... Titel                  | string  |                | Titel zur Anzeige im Markdown-Editor _[3]_ |
|                            |         |                | |
| Parser-Standardwerte       |         |                | |
| ... SafeMode               | boolean | false          | siehe _[4]_ |
| ... MarkupEscaped          | boolean | false          | siehe _[4]_ |
| ... BreaksEnabled          | boolean | false          | siehe _[4]_ |
| ... UrlsLinked             | boolean | true           | siehe _[4]_ |
| ... Inline                 | boolean | false          | siehe _[4]_ |
|                            |         |                | |
| ... HtmlWrapper            | boolean | true           | HTML-Code als Wrapper hinzufügen |
|                            |         |                | |
| Editor-Konfiguration       |         |                | |
| ... spellChecker           | boolean | false          | siehe _[5]_ |
| ... codeSyntaxHighlighting | boolean | false          | siehe _[5]_ |

_[1]_: Variable vom Typ "String", das den original Markdown-Code enthält

_[2]_: Variable vom Typ "String" mit dem Variablenprofil "~HTMLBox", in diese Variablen werden beim Speichern im Editor der in HTML konvertierte Inhalt geschrieben

_[3]_: optionale @ngane eines Titels des Editors, wird hier nichts angegeben, wird die Bezeichnung der Variable ausgegeben.

_[4]_: Erklärung der Optionen siehe in [Github](https://github.com/erusev/parsedown#readme) bzw. im [Tutorial](https://github.com/erusev/parsedown/wiki/Tutorial:-Get-Started).

_[5]_: Erklärung der Konfiguration siehe in [Github](https://github.com/sparksuite/simplemde-markdown-editor).

##### Markdown-Editor

Um die Erfassung solcher Texte maximal zu unterstützen kann ein Editor aufgerufen werden. Hierzu wird der Webhook mit dem angehängten Kommando _editor_ ausgelöst.

`<IPSymcon-URL>:<Port>/hook/Markdown/editor?<ID der Markdown-Variable>&<ID der HTML-Variable>`

Es wird überprpft, ob die Markdown-Variable in der Instanz konfiguriert ist und ob die HTML-Variable dazu passt.
Zudem kann der Zugriff auf diesen Webhook per _BasicAuth_ abgesichert werden. Sollte es den Bedarf geben, in einer Installation unterschiedliche Berechtigungen
zu vergeben, können weitere Instanzen angelegt werden, die getrennt konfiguriert werden.

Mit einer String-Variable mit dem Variablenprofil "~HTMLBox" mit einem der folgenden Inhalte kann der Editor für eine bestimmte Variable aufgerufen werden:

Integriert in die Web-GUI

```
<iframe width="100%" height="360" src="<IPSymcon-URL>:<Port>/hook/Markdown/editor?markdown_varID=<Markdown-ID>&html_varID=<HTML-ID>"></iframe>
```

Aufruf in einem externen Browser-Fenster

```
<a href="<IPSymcon-URL>:<Port>/hook/Markdown/editor?markdown_varID=<Markdown-ID>&html_varID=<HTML-ID>"/>Editor</a>
```

Anmerkung (aus Erfahrung]: die mit sputzen Klammern gekennzeichneten \<Platzhalter\> müssen natürlich mit den entsprechenden Werten gefüllt werden.

#### Aktionen

| Bezeichnung                | Beschreibung |
| :------------------------- | :----------- |
| Editor öffnen              | Editor für die ausgewählte Variable öffnen, dabei wird vorzugsweise die _ipmagic-URL_ verwendet |

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
- der eingebundene Javascript-basierter Editor ist der [SimpleMDE](https://github.com/sparksuite/simplemde-markdown-editor)

## 7. Versions-Historie

- 1.6 @ 21.02.2024 11:10
  - Absicherung auf fehlende Variablen
  - update submodule CommonStubs

- 1.5 @ 06.02.2024 09:46
  - Verbesserung: Angleichung interner Bibliotheken anlässlich IPS 7
  - update submodule CommonStubs

- 1.4 @ 03.11.2023 11:06
  - Neu: Ermittlung von Speicherbedarf und Laufzeit (aktuell und für 31 Tage) und Anzeige im Panel "Information"
  - update submodule CommonStubs

- 1.3 @ 04.07.2023 14:44
  - Vorbereitung auf IPS 7 / PHP 8.2
  - update submodule CommonStubs
    - Absicherung bei Zugriff auf Objekte und Inhalte

- 1.2 @ 24.10.2022 10:20
  - Neu: Markdown-Editor kann direkt von der Instanz-Konfiguration ausgerufen werden
  - Neu: Konfiguration des Markdown-Editors

- 1.1 @ 21.10.2022 17:41
  - Neu: Einbindung eines Markdown-Editors, der Webhook aufgerufen werden kann

- 1.0 @ 19.10.2022 16:48
  - Initiale Version
