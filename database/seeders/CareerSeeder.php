<?php

namespace Database\Seeders;

use App\Models\Career;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CareerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Warehouse & Logistics Employee position
        Career::create([
            'start_date' => now(),
            'location' => 'Spreenhagen (Brandenburg)',
            'published' => true,

            // German content (primary)
            'position_de' => 'Mitarbeiter Lager & Logistik (M/W/*)',
            'tasks_de' => '<ul>
                <li>Warenannahme mit Mengenerfassung, Palettieren und Verpacken</li>
                <li>Einbuchung der Ware in das Warenwirtschaftssystem</li>
                <li>Überprüfung von Retouren auf Zustand und Vollständigkeit</li>
                <li>Be- und Entladung von LKW und Containern</li>
                <li>Kommissionierung von Waren für Bestellungen</li>
                <li>Versandvorbereitung durch Verpackung und Qualitätskontrolle</li>
            </ul>',
            'profile_de' => '<ul>
                <li>Berufserfahrung als Lager- oder Transportmitarbeiter</li>
                <li>Verständnis für logistische Abläufe</li>
                <li>Staplerschein (von Vorteil) und grundlegende EDV-Kenntnisse</li>
                <li>Körperliche Fitness und Belastbarkeit</li>
                <li>Flexibilität in verschiedenen logistischen Bereichen</li>
                <li>Deutschkenntnisse in Wort und Schrift</li>
                <li>Englischkenntnisse von Vorteil</li>
                <li>Selbstständige Arbeitsweise</li>
                <li>Teamfähigkeit</li>
                <li>Anpackende Mentalität mit hohem Engagement</li>
            </ul>',
            'expectations_de' => '<ul>
                <li>Position in einem international tätigen Unternehmen mit etablierter Marktposition</li>
                <li>Abwechslungsreiche Tätigkeiten</li>
                <li>Internationale Ausrichtung</li>
                <li>Beschäftigung in einem wachsenden Unternehmen</li>
                <li>Gutes Betriebsklima</li>
                <li>Teamorientierte Arbeitsweise</li>
            </ul>',

            // English translations
            'position_en' => 'Warehouse & Logistics Employee (M/F/*)',
            'tasks_en' => '<ul>
                <li>Receiving goods with quantity capture, palletizing, and packaging</li>
                <li>Booking items into the warehouse management system</li>
                <li>Inspecting returned merchandise for condition and completeness</li>
                <li>Loading and unloading trucks and containers</li>
                <li>Picking goods for orders</li>
                <li>Preparing shipments through packaging and quality checks</li>
            </ul>',
            'profile_en' => '<ul>
                <li>Prior experience as warehouse or transport worker</li>
                <li>Understanding of logistics processes</li>
                <li>Forklift certification (preferred) and basic computer skills</li>
                <li>Physical fitness and stamina</li>
                <li>Flexibility across different logistical areas</li>
                <li>German language proficiency in speech and writing</li>
                <li>English skills advantageous</li>
                <li>Independent work capability</li>
                <li>Team collaboration abilities</li>
                <li>Hands-on mentality with high commitment</li>
            </ul>',
            'expectations_en' => '<ul>
                <li>Position at an international company with established market positioning</li>
                <li>Variety in duties</li>
                <li>International scope</li>
                <li>Employment within an expanding enterprise</li>
                <li>Good workplace atmosphere</li>
                <li>Team-oriented work environment</li>
            </ul>',

            // French translations
            'position_fr' => 'Employé Entrepôt & Logistique (H/F/*)',
            'tasks_fr' => '<ul>
                <li>Réception des marchandises avec saisie des quantités, palettisation et emballage</li>
                <li>Enregistrement des marchandises dans le système de gestion</li>
                <li>Vérification des retours pour l\'état et l\'intégralité</li>
                <li>Chargement et déchargement de camions et conteneurs</li>
                <li>Préparation des commandes</li>
                <li>Préparation des expéditions par emballage et contrôle qualité</li>
            </ul>',
            'profile_fr' => '<ul>
                <li>Expérience professionnelle en tant qu\'employé d\'entrepôt ou de transport</li>
                <li>Compréhension des processus logistiques</li>
                <li>Certificat de cariste (un avantage) et connaissances informatiques de base</li>
                <li>Forme physique et résistance</li>
                <li>Flexibilité dans différents domaines logistiques</li>
                <li>Maîtrise de l\'allemand à l\'oral et à l\'écrit</li>
                <li>Compétences en anglais avantageuses</li>
                <li>Capacité à travailler de manière autonome</li>
                <li>Esprit d\'équipe</li>
                <li>Mentalité proactive avec un fort engagement</li>
            </ul>',
            'expectations_fr' => '<ul>
                <li>Poste dans une entreprise internationale avec une position établie sur le marché</li>
                <li>Activités variées</li>
                <li>Orientation internationale</li>
                <li>Emploi dans une entreprise en croissance</li>
                <li>Bonne atmosphère de travail</li>
                <li>Environnement de travail orienté équipe</li>
            </ul>',

            // Dutch translations
            'position_nl' => 'Medewerker Magazijn & Logistiek (M/V/*)',
            'tasks_nl' => '<ul>
                <li>Goederenontvangst met hoeveelheidsregistratie, palletteren en verpakken</li>
                <li>Boeking van goederen in het voorraadbeheersysteem</li>
                <li>Controle van retouren op staat en volledigheid</li>
                <li>Laden en lossen van vrachtwagens en containers</li>
                <li>Orderpicking van goederen</li>
                <li>Verzendvoorbereiding door verpakking en kwaliteitscontrole</li>
            </ul>',
            'profile_nl' => '<ul>
                <li>Werkervaring als magazijn- of transportmedewerker</li>
                <li>Begrip van logistieke processen</li>
                <li>Heftruckcertificaat (voorkeur) en basiscomputervaardigheden</li>
                <li>Fysieke fitheid en uithoudingsvermogen</li>
                <li>Flexibiliteit in verschillende logistieke gebieden</li>
                <li>Duitse taalvaardigheid in woord en geschrift</li>
                <li>Engelse vaardigheden voordelig</li>
                <li>Zelfstandig werkvermogen</li>
                <li>Teamsamenwerkingsvaardigheden</li>
                <li>Aanpakmentaliteit met hoge inzet</li>
            </ul>',
            'expectations_nl' => '<ul>
                <li>Functie bij een internationaal bedrijf met gevestigde marktpositie</li>
                <li>Afwisselende taken</li>
                <li>Internationale reikwijdte</li>
                <li>Tewerkstelling binnen een groeiende onderneming</li>
                <li>Goede werksfeer</li>
                <li>Teamgerichte werkomgeving</li>
            </ul>',

            // Polish translations
            'position_pl' => 'Pracownik Magazynu i Logistyki (M/K/*)',
            'tasks_pl' => '<ul>
                <li>Przyjmowanie towarów z rejestracją ilości, paletyzacją i pakowaniem</li>
                <li>Wprowadzanie towarów do systemu zarządzania magazynem</li>
                <li>Kontrola zwrotów pod kątem stanu i kompletności</li>
                <li>Załadunek i rozładunek ciężarówek i kontenerów</li>
                <li>Kompletowanie towarów do zamówień</li>
                <li>Przygotowanie przesyłek poprzez pakowanie i kontrolę jakości</li>
            </ul>',
            'profile_pl' => '<ul>
                <li>Doświadczenie zawodowe jako pracownik magazynu lub transportu</li>
                <li>Zrozumienie procesów logistycznych</li>
                <li>Uprawnienia na wózki widłowe (preferowane) i podstawowa znajomość komputera</li>
                <li>Sprawność fizyczna i wytrzymałość</li>
                <li>Elastyczność w różnych obszarach logistycznych</li>
                <li>Znajomość języka niemieckiego w mowie i piśmie</li>
                <li>Znajomość języka angielskiego mile widziana</li>
                <li>Umiejętność samodzielnej pracy</li>
                <li>Umiejętność współpracy w zespole</li>
                <li>Podejście praktyczne z wysokim zaangażowaniem</li>
            </ul>',
            'expectations_pl' => '<ul>
                <li>Stanowisko w międzynarodowej firmie z ugruntowaną pozycją rynkową</li>
                <li>Różnorodne obowiązki</li>
                <li>Międzynarodowy zasięg</li>
                <li>Zatrudnienie w rozwijającej się firmie</li>
                <li>Dobra atmosfera w pracy</li>
                <li>Środowisko pracy zorientowane na zespół</li>
            </ul>',
        ]);
    }
}
