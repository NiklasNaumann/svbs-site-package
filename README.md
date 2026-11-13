Site Package for the project "SV Bad Schwartau"
==============================================================

TYPO3 13.4 site package based on Bootstrap Package 16.

## Newsticker

Im TYPO3-Seitenmodul ein neues Inhaltselement **Newsticker** anlegen und an der
gewünschten Position platzieren, zum Beispiel unter dem Vereinslogo auf der
Startseite. Unter **Kurzmeldungen** eine Meldung pro Zeile eingeben. Leerzeilen
werden ignoriert; die Meldungen werden als reiner Text ausgegeben.

Die optionale Bezeichnung dient als zugängliche Überschrift und wird visuell
ausgeblendet. Ohne Bezeichnung heißt der Bereich „Kurzmeldungen“. Ein leerer
Ticker wird nicht ausgegeben.

Das Laufband lässt sich mit **Pause / Fortsetzen** anhalten. Es pausiert auch,
wenn die Maus über den Meldungen steht oder der Bereich Tastaturfokus erhält.
Ohne JavaScript oder bei reduzierten Bewegungseinstellungen erscheinen die
Meldungen als normale Liste. Es werden keine zusätzlichen Datenbankfelder und
keine weitere Extension benötigt.

Nach Veröffentlichung eines neuen Commits auf dem Server mit `update_svbs`
aktualisieren und anschließend das Inhaltselement im Backend anlegen.
