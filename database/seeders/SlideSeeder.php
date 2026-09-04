<?php

namespace Database\Seeders;

use App\Models\Slide;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SlideSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $slides = [
      [
        'title' => 'Zoom PodTrak P4Next',
        'caption_en' => 'The PodTrak P4next features 4 XLR inputs and next-gen features like AI noise reduction, tone shaping, and voice compression making broadcast-quality podcasting easier than ever.',
        'caption_de' => 'Der PodTrak P4next bietet 4 XLR-Eingänge und coole Funktionen wie KI-Rauschunterdrückung, Klangformung und Sprachkompression, die Podcasting in Broadcast-Qualität einfacher denn je machen.',
        'caption_fr' => 'Le PodTrak P4next est doté de 4 entrées XLR et de fonctionnalités de nouvelle génération telles que la réduction du bruit par IA, la mise en forme du son et la compression vocale, ce qui rend l\'enregistrement de podcasts de qualité professionnelle plus facile que jamais.',
        'caption_nl' => 'De PodTrak P4next beschikt over 4 XLR-ingangen en geavanceerde functies zoals AI-ruisonderdrukking, toonvorming en spraakcompressie, waardoor podcasten van uitzendkwaliteit eenvoudiger dan ooit is.',
        'caption_pl' => 'PodTrak P4next oferuje 4 wejścia XLR i funkcje nowej generacji, takie jak redukcja szumów AI, kształtowanie tonów i kompresja głosu, dzięki którym tworzenie podcastów w jakości nadawczej jest łatwiejsze niż kiedykolwiek.',
        'background' => 'https://media.sound-service.eu/images/slider/Zoom%20P4next%20ShopSlider.jpg',
        'video' => 'https://www.youtube.com/watch?v=aGST6IjC7l4',
        'link' => '/zoom/podtrak-p4next',
        'order' => 1,
        'active' => true,
      ],
      [
        'title' => 'ESP USA',
        'caption_en' => 'In 2014, the ESP USA shop opened in North Hollywood CA to craft instruments that continue ESP\'s highly-acclaimed tradition of excellence in guitar building.',
        'caption_de' => 'Seit 2014 werden im ESP USA Shop in North Hollywood Instrumente auf höchstem handwerklichen Niveau gebaut, die ESPs jahrzehntelange Tradition im Gitarrenbau fortsetzen.',
        'caption_fr' => 'En 2014, l\'atelier ESP USA a ouvert ses portes à North Hollywood, en Californie, pour fabriquer des instruments qui perpétuent la tradition d\'excellence d\'ESP en matière de fabrication de guitares, une tradition très réputée.',
        'caption_nl' => 'In 2014 opende de ESP USA-werkplaats in North Hollywood, Californië, haar deuren om instrumenten te vervaardigen die de hoog aangeschreven traditie van ESP op het gebied van gitaarbouw van topkwaliteit voortzetten.',
        'caption_pl' => 'W 2014 roku w North Hollywood w Kalifornii otwarto sklep ESP USA, w którym powstają instrumenty kontynuujące cieszącą się ogromnym uznaniem tradycję ESP w budowaniu doskonałych gitar.',
        'background' => 'https://media.sound-service.eu/images/slider/ZoomH6studioShopSlider.jpg',
        'video' => 'https://www.youtube.com/watch?v=yUeOrHFeoqI',
        'link' => '/brands/esp',
        'order' => 2,
        'active' => true,
      ],
      [
        'title' => 'Zoom H6 Studio',
        'caption_en' => 'The H6studio puts studio-grade sound in your hands. With 6 audio tracks, large-diaphragm XY mics, 32-bit float recording, and F-Series preamps, you can capture inspiration wherever it strikes.',
        'caption_de' => 'Das H6studio bringt dir Sound in Studioqualität. Mit 6 Audiospuren, Großmembran-XY-Mikrofonen, 32-Bit-Float-Aufnahme und Vorverstärkern der F-Serie kannst du deine musikalischen Ideen überall festhalten.',
        'caption_fr' => 'L\'H6studio met un son de qualité studio à votre portée. Avec 6 pistes audio, des microphones XY à large diaphragme, un enregistrement 32 bits flottant et des préamplis de la série F, vous pouvez capturer l\'inspiration où qu\'elle se présente.',
        'caption_nl' => 'De H6studio geeft je geluid van studiokwaliteit in handen. Met 6 audiosporen, XY-microfoons met groot membraan, 32-bits floating-point opname en F-serie voorversterkers kun je inspiratie vastleggen waar die ook opkomt.',
        'caption_pl' => 'H6studio oddaje w Twoje ręce dźwięk klasy studyjnej. Dzięki 6 ścieżkom audio, mikrofonom XY o dużej membranie, 32-bitowemu nagrywaniu zmiennoprzecinkowemu i przedwzmacniaczom serii F możesz uchwycić inspirację, gdziekolwiek się pojawi.',
        'background' => 'https://media.sound-service.eu/images/slider/ZoomH6studioShopSlider.jpg',
        'video' => 'https://www.youtube.com/watch?v=tTIqL0I3HMQ',
        'link' => '/zoom/h6studio',
        'order' => 3,
        'active' => true,
      ],
    ];

    foreach ($slides as $slide) {
      Slide::create($slide);
    }
  }
}
