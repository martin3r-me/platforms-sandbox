<?php

namespace Platform\Sandbox\Enums;

enum SandboxPhaseNumber: int
{
    case URGENCY = 1;
    case COALITION = 2;
    case VISION = 3;
    case VOLUNTEERS = 4;
    case BARRIERS = 5;
    case SHORT_WINS = 6;
    case SUSTAIN = 7;
    case ANCHOR = 8;

    public function label(): string
    {
        return match ($this) {
            self::URGENCY => 'Dringlichkeit erzeugen',
            self::COALITION => 'Führungskoalition aufbauen',
            self::VISION => 'Vision & Strategie entwickeln',
            self::VOLUNTEERS => 'Freiwillige gewinnen',
            self::BARRIERS => 'Hindernisse beseitigen',
            self::SHORT_WINS => 'Kurzfristige Erfolge erzielen',
            self::SUSTAIN => 'Veränderung weiter vorantreiben',
            self::ANCHOR => 'Veränderung verankern',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::URGENCY => 'Dringlichkeit',
            self::COALITION => 'Koalition',
            self::VISION => 'Vision',
            self::VOLUNTEERS => 'Freiwillige',
            self::BARRIERS => 'Hindernisse',
            self::SHORT_WINS => 'Quick Wins',
            self::SUSTAIN => 'Vorantreiben',
            self::ANCHOR => 'Verankern',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::URGENCY => 'Warum ist die Veränderung jetzt notwendig? Dringlichkeit kommunizieren und Bewusstsein schaffen.',
            self::COALITION => 'Ein starkes Team mit Einfluss und Kompetenz zusammenstellen, das die Veränderung führt.',
            self::VISION => 'Eine klare, motivierende Vision formulieren und die Strategie zur Umsetzung definieren.',
            self::VOLUNTEERS => 'Breite Unterstützung gewinnen, Betroffene einbinden und die Vision kommunizieren.',
            self::BARRIERS => 'Strukturelle und organisatorische Hindernisse identifizieren und beseitigen.',
            self::SHORT_WINS => 'Schnelle, sichtbare Erfolge planen und feiern, um Momentum aufzubauen.',
            self::SUSTAIN => 'Erfolge nutzen, um weitere Veränderungen voranzutreiben. Nicht nachlassen.',
            self::ANCHOR => 'Neue Verhaltensweisen und Prozesse in der Unternehmenskultur verankern.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::URGENCY => 'heroicon-o-fire',
            self::COALITION => 'heroicon-o-user-group',
            self::VISION => 'heroicon-o-light-bulb',
            self::VOLUNTEERS => 'heroicon-o-megaphone',
            self::BARRIERS => 'heroicon-o-shield-check',
            self::SHORT_WINS => 'heroicon-o-trophy',
            self::SUSTAIN => 'heroicon-o-arrow-trending-up',
            self::ANCHOR => 'heroicon-o-building-library',
        };
    }

    public function color(): string
    {
        // 8 Kotter-Phasen auf die zentrale nx-Tone-Palette (gedämpft, kalm)
        return match ($this) {
            self::URGENCY => 'var(--nx-tone-rose)',
            self::COALITION => 'var(--nx-tone-amber)',
            self::VISION => 'var(--nx-tone-pink)',
            self::VOLUNTEERS => 'var(--nx-tone-emerald)',
            self::BARRIERS => 'var(--nx-tone-sky)',
            self::SHORT_WINS => 'var(--nx-tone-teal)',
            self::SUSTAIN => 'var(--nx-tone-indigo)',
            self::ANCHOR => 'var(--nx-tone-slate)',
        };
    }

    public function colorRgb(): string
    {
        return match ($this) {
            self::URGENCY => '230, 57, 70',
            self::COALITION => '231, 111, 81',
            self::VISION => '244, 211, 94',
            self::VOLUNTEERS => '167, 201, 87',
            self::BARRIERS => '69, 123, 157',
            self::SHORT_WINS => '42, 157, 143',
            self::SUSTAIN => '29, 53, 87',
            self::ANCHOR => '38, 70, 83',
        };
    }

    public function shape(): string
    {
        return match ($this) {
            self::URGENCY => 'triangle',
            self::COALITION => 'diamond',
            self::VISION => 'triangle',
            self::VOLUNTEERS => 'hexagon',
            self::BARRIERS => 'circle',
            self::SHORT_WINS => 'pentagon',
            self::SUSTAIN => 'octagon',
            self::ANCHOR => 'square',
        };
    }

    /**
     * Praxis-Tipp: Was in dieser Phase am wichtigsten ist.
     */
    public function tip(): string
    {
        return match ($this) {
            self::URGENCY => 'Zeigen Sie Daten und Fakten — nicht Meinungen. Eine echte Krise oder verpasste Chance wirkt staerker als jede Praesentation.',
            self::COALITION => 'Mischen Sie Hierarchie-Ebenen. Die Koalition braucht sowohl Entscheider als auch informelle Meinungsfuehrer aus dem Team.',
            self::VISION => 'Die Vision muss in 5 Minuten erklaerbar sein. Wenn sie laenger braucht, ist sie zu komplex.',
            self::VOLUNTEERS => 'Suchen Sie die 20% Begeisterten (siehe Normalverteilung). Diese ueberzeugen die 60% Unentschlossenen — nicht Sie.',
            self::BARRIERS => 'Die groessten Hindernisse sind selten technisch. Suchen Sie nach Prozessen, Strukturen und Gewohnheiten, die der Veraenderung widersprechen.',
            self::SHORT_WINS => 'Waehlen Sie Erfolge, die sichtbar, eindeutig und mit der Vision verknuepft sind. Feiern Sie oeffentlich.',
            self::SUSTAIN => 'Nach den ersten Erfolgen lauert die groesste Gefahr: Selbstzufriedenheit. Erhoehen Sie das Tempo, nicht verlangsamen.',
            self::ANCHOR => 'Veraenderung ist erst verankert, wenn sie ueberlebt, auch wenn die Fuehrungskoalition sich zurueckzieht.',
        };
    }

    /**
     * Die zentrale Frage, die in dieser Phase beantwortet werden muss.
     */
    public function keyQuestion(): string
    {
        return match ($this) {
            self::URGENCY => 'Was passiert, wenn wir NICHTS veraendern?',
            self::COALITION => 'Wer hat genug Einfluss UND Glaubwuerdigkeit, um andere mitzuziehen?',
            self::VISION => 'Wie sieht der Zielzustand konkret aus — und warum lohnt er sich?',
            self::VOLUNTEERS => 'Wie erreichen wir die 60% Unentschlossenen ueber unsere Begeisterten?',
            self::BARRIERS => 'Welche Struktur oder Regel steht der Veraenderung im Weg?',
            self::SHORT_WINS => 'Welchen sichtbaren Erfolg koennen wir in den naechsten 2-4 Wochen erzielen?',
            self::SUSTAIN => 'Was sind die naechsten drei konkreten Veraenderungen, die auf den Erfolgen aufbauen?',
            self::ANCHOR => 'Welche Rituale, Prozesse oder Strukturen sichern das neue Verhalten dauerhaft?',
        };
    }

    /**
     * Haeufigster Fehler in dieser Phase.
     */
    public function commonMistake(): string
    {
        return match ($this) {
            self::URGENCY => 'Zu wenig Dringlichkeit — das Team denkt, es geht auch ohne Veraenderung.',
            self::COALITION => 'Nur Manager einbinden, keine informellen Leader.',
            self::VISION => 'Vision ist zu abstrakt oder zu technisch — keiner kann sie nacherzaehlen.',
            self::VOLUNTEERS => 'Versuchen, die 20% Gegner zu ueberzeugen statt die 60% Mitte zu gewinnen.',
            self::BARRIERS => 'Hindernisse ignorieren und hoffen, dass Motivation allein reicht.',
            self::SHORT_WINS => 'Zu grosse Ziele setzen — der erste Erfolg kommt zu spaet.',
            self::SUSTAIN => 'Nach dem ersten Erfolg den Sieg erklaeren und nachlassen.',
            self::ANCHOR => 'Neue Prozesse einfuehren, aber alte nicht abschaffen.',
        };
    }

    public function needsDarkText(): bool
    {
        return match ($this) {
            self::VISION, self::VOLUNTEERS => true,
            default => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
