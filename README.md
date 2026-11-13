Site Package for the project "SV Bad Schwartau"
==============================================================

TYPO3 13.4 site package based on Bootstrap Package 16.

## Newsticker

Die Administration legt einmalig im TYPO3-Seitenmodul ein Inhaltselement
**Newsticker** an und platziert es an der gewünschten Position, zum Beispiel
unter dem Vereinslogo auf der Startseite. Bestehende Ticker bleiben erhalten.

Redakteure bearbeiten die Texte danach direkt unter **Web > Newsticker** in
der linken Modulleiste. Ein Zugriff auf das Seitenmodul ist dafür nicht nötig.
Unter **Kurzmeldungen** eine Meldung pro Zeile eingeben und **Speichern** wählen.
Leerzeilen werden ignoriert; die Meldungen werden als reiner Text ausgegeben.
Alle Meldungen entfernen, um den Ticker leer zu lassen. Beim Speichern wird der
Seitencache durch TYPO3 aktualisiert.

### Rechte für Redakteure

In der Backend-Benutzergruppe das Modul **Web > Newsticker** freigeben. Das
Seitenmodul muss nicht freigegeben werden. Zusätzlich sind nötig:

- Tabelle **Seiteninhalt (tt_content)** zum Bearbeiten freigeben.
- Den Inhaltstyp **Newsticker** erlauben und das Feld **Text (bodytext)**
  freigeben, sofern es als ausgeschlossenes Feld konfiguriert ist.
- Einen Datenbank-Mount auf die Seite mit dem Ticker sowie dort das
  Seitenrecht **Inhalt bearbeiten** vergeben; die Sprache muss erlaubt sein.

Das Modul zeigt nur bearbeitbare Newsticker an und verändert ausschließlich
deren Kurzmeldungen. Die normalen TYPO3-Rechte gelten weiterhin. Es ist für
den Live-Arbeitsbereich registriert; andere Inhaltstypen werden nicht angeboten.

Die optionale Bezeichnung dient als zugängliche Überschrift und wird visuell
ausgeblendet. Ohne Bezeichnung heißt der Bereich „Kurzmeldungen“. Ein leerer
Ticker wird nicht ausgegeben.

Das Laufband hat keinen Pause-Button. Es pausiert,
wenn die Maus über den Meldungen steht oder der Bereich Tastaturfokus erhält.
Ohne JavaScript oder bei reduzierten Bewegungseinstellungen erscheinen die
Meldungen als normale Liste. Es werden keine zusätzlichen Datenbankfelder und
keine weitere Extension benötigt.

Nach Veröffentlichung eines neuen Commits auf dem Server mit `update_svbs`
aktualisieren, das Backend neu laden und die Gruppenrechte vergeben.
