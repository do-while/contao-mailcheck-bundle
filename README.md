# contao-mailcheck-bundle

Contao-Erweiterung, die Formulareingaben **vor dem Mailversand** auf
typische Bot-/Spam-Muster prüft und die Mail bei Verdacht entweder still
verwirft oder markiert versendet – ohne dass der Absender eine
Fehlermeldung sieht (kein Lerneffekt für Bots).

Details zur internen Funktionsweise (Hooks, Klassen, Verifikation) stehen
in [DOKUMENTATION.md](DOKUMENTATION.md). Dieses README richtet sich an
alle, die das Bundle einbinden und im Backend konfigurieren wollen.

## Voraussetzungen

- Contao 4.13 (Zielversion, produktiv im Einsatz)
- Contao 5.3 / 5.7 sollten ebenfalls funktionieren (klassischer
  TL_HOOKS-Mechanismus, per Contao-BC-Versprechen weiter unterstützt)
- PHP >= 8.1

## Installation

Composer-Paketname: `do-while/contao-mailcheck-bundle`.

```
composer require do-while/contao-mailcheck-bundle
```

Nach der Installation im Contao Manager (oder per `contao:migrate`) wird
die Datenbank um die neuen Felder `tl_form.mailcheck_checks` und
`tl_form.mailcheck_mode` erweitert.

## Verwendung im Backend

Jedes Formular hat unter **Formulareigenschaften** eine neue Legende
**„Spam-Prüfung“** mit zwei Feldern:

- **„Spam-Prüfungen“** (Mehrfachauswahl). **Standard: keine Prüfung
  ausgewählt = Aus.** Das Bundle greift also erst, wenn du es für ein
  Formular bewusst einschaltest. Contaos eigene Formular-Validierung
  läuft davon unabhängig immer weiter. Aktuell steht eine Prüfung zur
  Auswahl: **„Bot-Muster-Scoring (Standard)“** – erkennt u. a. zufällige
  Zeichenketten ohne Leerzeichen, CamelCase-artige Muster und lange
  Konsonantenketten in den Formularfeldern (Details siehe
  DOKUMENTATION.md). Es können mehrere Prüfungen gleichzeitig angehakt
  werden; schlägt eine davon an, gilt die Eingabe als Spam.
- **„Verhalten bei Spam-Verdacht“** (Auswahl, Standard „Mail verwerfen“):
  - **Mail verwerfen** – die Formular-Mail wird komplett unterdrückt und
    die Eingabe nicht in der Datenbank gespeichert (falls das Formular
    das überhaupt tut). Der Absender bekommt trotzdem die normale
    Erfolgsmeldung des Formulars zu sehen. Ist Notification Center
    installiert, wird auch dessen Benachrichtigung unterdrückt (siehe
    Hinweis in DOKUMENTATION.md – bitte selbst testen).
  - **Mail trotzdem senden, Betreff markieren** – die Mail wird ganz
    normal verschickt, aber der Betreff bekommt das Präfix `MAILSPAM! `
    vorangestellt, sodass du sie z. B. per Mail-Filter aussortieren oder
    einfach optisch erkennen kannst.

Beide Einstellungen sind reine Backend-Sache – für den normalen Einsatz
ist kein Eingriff in den Quellcode nötig.

## Eigene Prüfregeln ergänzen

Die Logik der Prüfungen selbst ist bewusst nicht im Backend
konfigurierbar, sondern reine Code-Sache (dafür ist das nur für
Entwickler relevant). Zwei Möglichkeiten, eine eigene Prüfung zu
ergänzen (sie erscheint danach automatisch als weitere Auswahl-Option im
Formular-Backend):

1. Eigene Klasse gegen `SpamCheckerInterface` implementieren und in
   `src/Resources/config/services.yaml` bei der `SpamCheckerRegistry`
   registrieren.
2. `DefaultSpamChecker` erweitern und nur die Methode `spamScore()`
   überschreiben.

Genaue Beispiele dazu in DOKUMENTATION.md.

## Lizenz

Proprietär, Softleister – Hagen Klemp.
