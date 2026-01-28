# Check 0.9.6

Hitta trasiga länkar. Utvecklad av Anna Svensson.

<p align="center"><img src="screenshot.png" alt="Skärmdump"></p>

## Hur man installerar ett tillägg

[Ladda ner ZIP-filen](https://github.com/annaesvensson/yellow-check/archive/refs/heads/main.zip) och kopiera den till din `system/extensions` mapp. [Läs mer om tillägg](https://github.com/annaesvensson/yellow-update/tree/main/readme-sv.md).

## Hur man hittar trasiga länkar

Du kan hitta trasiga länkar på [kommandoraden](https://github.com/annaesvensson/yellow-core/tree/main/readme-sv.md). Det är ganska normalt att sidor byter namn, filer tas bort och vissa länkar inte längre fungerar. Öppna ett terminalfönster. Gå till installationsmappen där filen `yellow.php` finns. Skriv `php yellow.php check`, du kan valfritt ange en mapp och en plats. Detta kommer att hitta trasiga länkar på din webbplats och visa dem på skärmen.

Om du inte vill att en sida ska granskas, ställ in `Generate: exclude` i [sidinställningar](https://github.com/annaesvensson/yellow-core/tree/main/readme-sv.md#inställningar-page) högst upp på en sida.

## Hur man fixar trasiga länkar

När du har hittat trasiga länkar på din webbplats är det dags att fixa dem. Du har två alternativ. Det första alternativet är att byta ut en trasig länk. Försök att hitta den nya platsen eller den nya URL:en. Det andra alternativet är att ta bort en trasig länk. Om det inte går att hitta en ersättningslänk finns det inget annat val än att ta bort föråldrade länken.

## Exempel

Innehållsfil med alternativ för att generera en statisk webbplats:

    ---
    Title: Exempelsida
    Generate: exclude
    ---
    Den här sidan granskas inte för trasiga länkar.

Hitta trasiga länkar på kommandoraden:

`php yellow.php check`  

Hitta trasiga länkar på kommandoraden, olika platser:

`php yellow.php check public /wiki/`  
`php yellow.php check public /blog/`  
`php yellow.php check public /help/how-to-make-a-small-website`  

## Tack

Detta tillägg använder [curl](https://github.com/curl/curl) av Daniel Stenberg. Tack för det användbara biblioteket.

Har du några frågor? [Få hjälp](https://datenstrom.se/sv/yellow/help/).
