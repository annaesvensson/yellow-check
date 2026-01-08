<p align="right"><a href="readme-de.md">Deutsch</a> &nbsp; <a href="readme.md">English</a> &nbsp; <a href="readme-sv.md">Svenska</a></p>

# Check 0.9.6

Defekte Links finden.

<p align="center"><img src="screenshot.png" alt="Bildschirmfoto"></p>

## Wie man eine Erweiterung installiert

[ZIP-Datei herunterladen](https://github.com/annaesvensson/yellow-check/archive/refs/heads/main.zip) und in dein `system/extensions`-Verzeichnis kopieren. [Weitere Informationen zu Erweiterungen](https://github.com/annaesvensson/yellow-update/tree/main/readme-de.md).

## Wie man defekte Links findet

Du kannst defekte Links in der [Befehlszeile](https://github.com/annaesvensson/yellow-core/tree/main/readme-de.md) finden. Es ist ganz normal dass Seiten umbenannt werden, Dateien entfernt werden und manche Links nicht mehr funktionieren. Öffne ein Terminalfenster. Gehe ins Installations-Verzeichnis, dort wo sich die Datei `yellow.php` befindet. Gib ein `php yellow.php check`, du kannst wahlweise ein Verzeichnis und einen Ort angeben. Das findet defekte Links auf deiner Webseite und zeigt sie auf dem Bildschirm an.

Falls du nicht willst dass eine Seite überprüft wird, kannst du `Generate: exclude` in den [Seiteneinstellungen](https://github.com/annaesvensson/yellow-core/tree/main/readme-de.md#einstellungen-seite) ganz oben auf einer Seite festlegen.

## Wie man defekte Links korrigiert

Sobald du defekte Links auf deiner Webseite gefunden hast ist es an der Zeit sie zu beheben. Du hast zwei Möglichkeiten. Die erste Möglichkeit besteht darin, einen defekten Link zu ersetzen. Versuche den neuen Ort oder die neue URL zu finden. Die zweite Möglichkeit besteht darin, einen defekten Link zu entfernen. Sollte es nicht möglich sein einen Ersatz-Link zu finden, bleibt nichts anderes übrig als den veralteten Link zu entfernen.

## Beispiele

Inhaltsdatei mit Option zum Generieren einer statischen Webseite:

    ---
    Title: Beispielseite
    Generate: exclude
    ---
    Diese Seite wird nicht auf defekte Links überprüft.

Defekte Links in der Befehlszeile finden:

`php yellow.php check`  

Defekte Links in der Befehlszeile finden, unterschiedliche Orte:

`php yellow.php check public /wiki/`  
`php yellow.php check public /blog/`  
`php yellow.php check public /help/how-to-make-a-small-website`  

## Danksagung

Diese Erweiterung verwendet [curl](https://github.com/curl/curl) von Daniel Stenberg. Danke für die nützliche Bibliothek.

## Entwickler

Anna Svensson. [Hilfe finden](https://datenstrom.se/de/yellow/help/).
