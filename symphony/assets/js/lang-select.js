document.addEventListener("DOMContentLoaded", function () {
    const translations = {
        en: {
            title: "Language selection",
            intro: "This installation can speak in different languages. Which one are you fluent in?",
            button: "Proceed with Installation"
        },
        de: {
            title: "Sprachauswahl",
            intro: "Diese Installation kann verschiedene Sprachen sprechen. Welche bevorzugst du?",
            button: "Installation fortsetzen"
        },
        fr: {
            title: "Sélection de la langue",
            intro: "Cette installation peut s’exprimer dans différentes langues. Quelle langue parlez-vous?",
            button: "Continuer l’installation"
        },
        es: {
            title: "Selección de idioma",
            intro: "Esta instalación puede hablar diferentes idiomas. ¿Cuál prefieres?",
            button: "Continuar con la instalación"
        },
        it: {
            title: "Selezione della lingua",
            intro: "Questa installazione può parlare in diverse lingue. Qual è quella che conosci meglio?",
            button: "Procedi con l'installazione"
        },
        fi: {
            title: "Kielen valinta",
            intro: "Tämä asennusohjelma osaa puhua useita kieliä. Mikä kieli on sinulle sujuva?",
            button: "Jatka asennusta"
        },
        no: {
            title: "Språkvalg",
            intro: "Denne installasjonen kan snakke forskjellige språk. Hvilket språk behersker du flytende?",
            button: "Fortsett med installasjonen"
        },
        nl: {
            title: "Taalkeuze",
            intro: "Deze installatie kan in verschillende talen spreken. Welke taal spreekt u vloeiend?",
            button: "Doorgaan met installatie"
        },
        sk: {
            title: "Výber jazyka",
            intro: "Táto inštalácia dokáže hovoriť v rôznych jazykoch. Ktorým z nich hovoríte plynule?",
            button: "Pokračovať v inštalácii"
        },
        pl: {
            title: "Wybór języka",
            intro: "Ta instalacja może mówić w różnych językach. Którym z nich posługujesz się biegle?",
            button: "Kontynuuj instalację"
        },
        dk: {
            title: "Sprogvalg",
            intro: "Denne installation kan tale på forskellige sprog. Hvilket sprog taler du flydende?",
            button: "Fortsæt med installationen"
        },
        se: {
            title: "Språkval",
            intro: "Denna installation kan tala olika språk. Vilket språk talar du flytande?",
            button: "Fortsätt med installationen"
        },
        el: {
            title: "Επιλογή γλώσσας",
            intro: "Αυτή η εγκατάσταση μπορεί να μιλήσει σε διαφορετικές γλώσσες. Ποια γλώσσα μιλάτε άπταιστα;",
            button: "Συνέχεια με την εγκατάσταση"
        },
        tr: {
            title: "Dil seçimi",
            intro: "Bu kurulum farklı dillerde konuşabilir. Hangi dili akıcı bir şekilde konuşuyorsunuz?",
            button: "Kuruluma devam et"
        }
        // … can be expanded as desired
    };

    const langSelect = document.querySelector('select[name="lang"]');
    if (!langSelect) return;

    const titleEl = document.querySelector("#installer-title");
    const introEl = document.querySelector("#installer-intro");
    const buttonEl = document.querySelector("#installer-submit");

    function applyLang(lang) {
        const t = translations[lang] || translations.en;
        const rtlLangs = ['ar', 'he', 'fa'];

        document.documentElement.lang = lang;
        document.documentElement.dir = rtlLangs.includes(lang) ? 'rtl' : 'ltr';
        if (titleEl) titleEl.textContent = t.title;
        if (introEl) introEl.textContent = t.intro;
        if (buttonEl) buttonEl.value = t.button;
    }

    // Changes made by user
    langSelect.addEventListener("change", function () {
        applyLang(this.value);
    });

    // If a language is already preset on server side:
    applyLang(langSelect.value);
});
