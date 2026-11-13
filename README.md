Site Package for the project "SV Bad Schwartau"
==============================================================

TYPO3 13.4 site package based on Bootstrap Package 16.

## Newsticker

Die Kurzmeldungen sind eigene Datensätze in der Tabelle
`tx_svbadschwartau_ticker_message`. Texte werden ausschließlich über
**Web > Newsticker** verwaltet. Das Inhaltselement **Newsticker** platziert nur
die Ausgabe; es hat keine Text- oder Überschriftenfelder.

### Einmalige Einrichtung

1. Auf dem Server `update_svbs` ausführen. Der Alias muss nach dem Composer-Update
   `php8.4-cli vendor/bin/typo3 extension:setup` ausführen, damit die neue Tabelle
   angelegt wird, und anschließend den TYPO3-Cache leeren.
2. Einen Systemordner für die Kurzmeldungen anlegen und dessen Seiten-ID merken.
3. Unter **Site Management > Settings** die Website auswählen und unter
   **Newsticker > Speicherordner für Kurzmeldungen (PID)** diese ID eintragen.
   Das Site-Set **SV Bad Schwartau** muss für die Website eingebunden sein.
   Technischer Schlüssel: `svbs.newsticker.storagePid`, Standardwert `0` (keine
   Ausgabe). Der Ordner darf auch außerhalb des Seitenbaums der Website liegen.
4. Das Inhaltselement **Newsticker** an der gewünschten Stelle platzieren.
   Bereits vorhandene Newsticker-Elemente können weiterverwendet werden.
5. Der Redaktion die unten genannten Rechte geben und das Backend neu laden.

Pro Website kann ein eigener Speicherordner konfiguriert werden. Mehrere
Websites können bei Bedarf denselben Ordner verwenden. Alle Ticker-Elemente
einer Website zeigen dieselben aktiven Meldungen an. Ohne konfigurierte PID
oder aktive Meldungen bleibt die Ausgabe leer. Alte Texte aus `tt_content`
werden nicht mehr angezeigt und nicht automatisch importiert; benötigte Texte
im Modul als einzelne Meldungen neu anlegen.

### Kurzmeldungen pflegen

**Web > Newsticker** funktioniert unabhängig von der aktuellen Auswahl im
Seitenbaum. Bei mehreren zugänglichen Websites wird die Website direkt im
Modul gewählt. Oben steht das Eingabefeld **Neue Kurzmeldung** mit
**Hinzufügen**, darunter die Liste aller vorhandenen Meldungen.

- Jede Meldung ist ein eigener Datensatz, maximal 1000 Zeichen, reiner Text.
- Neue Meldungen stehen oben und werden auch im Laufband zuerst ausgegeben.
- Unter **Bearbeiten** lässt sich eine einzelne Meldung ändern.
- **Ausblenden / Einblenden** steuert die Veröffentlichung.
- Unter **Löschen** wird das Löschen bestätigt. TYPO3 löscht die Datensätze
  zunächst nur logisch; sie können über den Papierkorb wiederhergestellt werden.

Schreibzugriffe laufen über TYPO3s DataHandler mit Rechteprüfung und Historie.
Formulare sind gegen CSRF und versehentliches Überschreiben zwischenzeitlich
geänderter Meldungen geschützt. Beim Speichern wird der Cache aller Seiten
mit diesem Speicherordner invalidiert, auch wenn der Ticker bisher leer war.

### Rechte für Redakteure

In der Backend-Benutzergruppe freigeben:

- Modul **Web > Newsticker**.
- Tabelle **Newsticker-Kurzmeldung** (`tx_svbadschwartau_ticker_message`) zum
  Lesen und Bearbeiten.
- Datenbank-Mount auf den konfigurierten Systemordner und dort das Seitenrecht
  **Inhalt bearbeiten**.

Rechte auf das Seitenmodul oder die Tabelle `tt_content` sind für die laufende
Pflege nicht nötig. Die Platzierung des Inhaltselements bleibt Aufgabe der
Administration. Das Modul ist für den Live-Arbeitsbereich registriert.

### Darstellung und Prüfung

Das Laufband hat keinen Pause-Button. Es pausiert, wenn die Maus über den
Meldungen steht oder der Bereich Tastaturfokus erhält. Ohne JavaScript oder
bei reduzierten Bewegungseinstellungen erscheinen die Meldungen als Liste.

Die eigenständigen Anwendungsprüfungen laufen ohne zusätzliche Testabhängigkeiten:

```bash
php Tests/Standalone/NewstickerTest.php
```

Sie prüfen mit kontrollierten API-Doubles Ordner-/Rechtegrenzen, CRUD,
Formularschutz, Datenfilterung und Cache-Tags. Die Tests starten kein TYPO3 und
ersetzen keinen Integrationstest im laufenden TYPO3-Backend.
