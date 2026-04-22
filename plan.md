wir erstellen hier ein wordpress plugin, welches von rallyestage.de event daten herunterläd, local cached, und daraus elemente in wordpress generiert.

baue im admin-ui eine seite die das configurieren von: base_url: https://api.rallyestage.de oder https://beta.rallyestage.de
aufnahme eines bearer tokens.

die api beschreibung findest du her: https://beta.rallyestage.de/api/public/openapi.json

das plugin soll liefern:

- shortcode für zeitplan. Erstelle einen tabelarischen zeitplan, pro Veranstaltungstag eine spalte, die Termine untereinander weg.

- wenn ein Termin is_wp == true hat, dann füge einen Link zu einer DEtailseite für Wertungsprüfungen. Stelle eine openstreetmap da, in der die Strecke sowie die ausgelieferten pois dargestellt werden.