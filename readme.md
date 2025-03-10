megaliga – instrukcja obsługi v.6.0

1. Wpisz wyniki z tabel z rundy zasadniczej, playin i playoff do tabeli megaliga_history

-   table_type:
    -   regular - sezon zasadniczy
    -   playin - faza play in
    -   playoff - faza play off
-   ligue_group:
    -   `dolce` lub `gabbana` dla rundy zasadniczhe
    -   `dolce&gabbana` dla fazy play in/off

2. Wprowadź wszystkich graczy do tabeli megaliga_user_data

3. Ustaw następujące wartości w kolumnach megaliga_user_data:

    - reached_playin = 0
    - reached_playoff = 0
    - player_draft_number = 1
    - player_draft_number_playoff = 1
    - (jeżeli wykorzystywane) w kolumnie credit_balance i credit_balance_playoff wstaw wartość kredytu przyznawanego dla każdego gracza
    - ligue_groups_id = 4 – reset przydziału do grupy
    - is_rookie - 1 - jeżeli jest to pierwszy sezon drużyny; 0 - drużyna obecna w lidze co najmniej 2 sezony,
    - is_draw_round1_draft_order = 0 - reset formularza wyboru kolejności w drafcie

4. W tabeli megaliga_draft_data wstaw:

-   1 w kolumnie draft_current_round_dolce
-   1 w kolumnie draft_current_round_gabbana
-   1 w kolumnie playoff_draft_current_round
-   countRookies = 1 - jeżeli losowanie przydziału do grupy ma uwzględniać podział zespołów na koszyki; 0 - brak podziału

5. W tabeli megaliga_1round_draft_order_lottery_outcome wstaw wartość NULL w kolumnach one, two, three, four, five, six dla obu wierszy reprezentujących grupę dolce i gabbana. W kolumnach one, two, three, four, five, six zapisane są ID graczy.

6. W tabeli megaliga_draft_order:
   a) dodaj po 6 rekordów dla każdej z grup:

    - position_name = 1..6
    - is_selected = 0
    - ligue_groups = 1 (dolce), 2 (gabbana)

    b) Jeżeli rekordy już są to dla wszystkich rekordów ustaw:

    - is_selected = 0

7. W tabeli megaliga_starting_lineup_status w polu "is_open" wstaw wartość 1 dla wszystkich rekordów.
   Pole to odpowiada za blokowanie formularza wyboru składu dla danej kolejki w sezonie zasadniczym, playin i playoff

8. Dodaj nowy sezon megaligi do tabeli megaliga_season

-   w polu „season_name” wprowadź rok, w którym odbywa się dana edycja megaligi
-   w polu „number_of_groups” podaj liczbę grup w danym sezonie megaligi
-   w polu „current” wpisz jeden dla obecnie rozgrywanego sezonu. Jednocześnie dla wszystkich pozostałych wpisów odpowiadającym poprzednim sezonom w pole „current” wpisz wartość = 0

9. Wpisz do megaliga_history_champion mistrza z kończącego się sezonu:

-   team_name
-   logo_url
-   season (np: 2022)

10. Wyczyść rekordy z następujących tabel:

-   megaliga_scores (AUTO_INCREMENT = 1)
-   megaliga_scores_playoff (AUTO_INCREMENT = 1)
-   megaliga_trainer_score (AUTO_INCREMENT = 1)
-   megaliga_trainer_score_playoff (AUTO_INCREMENT = 1)
-   megaliga_schedule (AUTO_INCREMENT = 1)
-   megaliga_schedule_playoff (AUTO_INCREMENT = 1)
-   megaliga_starting_lineup (AUTO_INCREMENT = 1)
-   megaliga_starting_lineup_playin (AUTO_INCREMENT = 1)
-   megaliga_starting_lineup_playoff (AUTO_INCREMENT = 1)
-   megaliga_season_draft_order_dolce (AUTO_INCREMENT = 1)
-   megaliga_season_draft_order_gabbana (AUTO_INCREMENT = 1)
-   megaliga_playoff_draft_order (AUTO_INCREMENT = 1)

11. Wpisz aktualne daty kolejek w tabeli megaliga_round_calendar

12. Dokonaj aktualizacji tabeli megaliga_players

-   usuń zawodników nie istniejących
-   dodaj nowych zawodników
-   dla każdego zawodnika uzupełnij pola credit (wartość zawodnika w drafcie do sezonu zasadniczego) i credit_playoff (pole opcjonalne; wartość zawodnika w drafcie do playoff)
-   usuń przypisanie do graczy w rundzie zasadniczej i w fazie playoff
-   usuń numer w drafcie, do sezonu zasadniczego i playoff, z którym zostali wybrani do zespołu

13. Uruchom losowanie przydziału do grup w widoku "Drużyny" poprzez ustawienie w kolumnie megaliga_draft_data.group_lottery_open = 1
    I. meliga_draft_data.countRookies = 1 - jeżeli w sezonie są drużyny "beniaminki":

-   losowanie odbywa się z podziałem na koszyki
    a) 1 koszyk - 8 drużyn będących co najmniej 2 sezony
    b) 2 koszyk - 4 drużyny będące beniaminkiem
-   w danej grupie są 4 drużyny weterani i 2 drużyny beniaminki

II. meliga_draft_data.countRookies = 0 - jeżeli w sezonie nie ma drużyn "beniaminków"

14. Uruchom losowanie kolejności wyboru w pierwszej rundzie draftu:

-   megaliga_draft_data.draft_round1_order_lottery_open = 1

15. Kolejność wyboru w drafcie w fazie playoff generuje się automatycznie

16. Jeżeli w danym sezonie megaliga dodane są nowe grupy to dodaj je do tabeli megaliga_ligue_groups (to ma wpływ na użyte szablony stron; zmiany grup wymagaja implementacji zmian w kodzie)

17. Draft do rundy zasadniczej

-   dokonaj konfiguracji draftu w tabeli megaliga_draft_data
    a) draft_window_open = 1 – udostepnij formularz do draftowania zawodnikow; 0 – ukryj formularz draftu
    b) draft_credit_enabled = 1 – draft uwzględnia wartość zawodników i dostępny kredyt gracza. W formularzu draftu, na liście wyboru zawodników pojawią się tylko Ci zawodnicy, na których stać będzie danego gracza; 0 – draft nie uwzglednia wartości zawodników
    c) wejdź do widoku "draft->kolejność wyboru->runda zasadnicza" aby załadować tabelę z kolejnością wyboru
-   w panelu administracyjnym w zakładce: WPTables najeżdżamy myszką na tabele „Draft Table – Regular Season” i wybieramy opcje „edit”. W zależności od wybranej opcji draft_credit_enabled zaznaczamy/odnzaczamy checkbox przy polu „Cena”. To spowoduje pojawienie/ukrycie się kolumny Cena w tabeli z zawodnikami do draftu w zakładce „Draft->Runda Zasadnicza”
-   po zakończonym drafcie ustawiamy w tabeli megaliga_draft_data pole draft_window_open na wartość 0 (ukrycie formularza).
-   formularz widoczny jest w danym momencie, tylko dla gracza, którego kolej wypada. Po wybraniu zawodnika lub spasowaniu, system udostępnia formularz następnemu graczowi w kolejności
-   gracz może ominąć kolejkę i nie wybierać zawodnika poprzez naciśnięcie przyciku „Pas”.

18. Rozpiska meczy dla rundy zasadniczej generuje się automatycznie za pomocą przycisku `Generuj terminarz dla rundy zasadniczej` w zakładce `wyniki->megaliga->kolejka 1`. Guzik będzie widoczny, gdy przydział zespołów do grup będzie zakończony i żaden wynik meczu nie zostanie zapisany.

Zasady generowania terminarza:

-   Pierwsze 10 kolejek to mecz i rewanż zespołow w ramach grupy (`dolce` lub `gabbana`)
-   Kolejki 11-16 to mecze pomiędzy drużynami z przeciwnej grupy (bez rewanżu)

19. Przygotowanie fazy playoff

    Do fazy playoff awansuje 3 zwycięzców dwumeczów z fazy playin oraz lucky loser, który zdobył największą liczbę punktów spośród tych, którzy przegrali w fazie playin

    Administrator ligi w zakładce `wyniki->playins->kolejka 2` będzie miał dostępny guzik `Generuj terminarz dla fazy play off (półfinał)`, który automatycznie przygotuje terminarz dla fazy półfinałowej wraz z oznaczeniem w tabeli megaliga_user_data drużyn które osiągnęły faze playoff.

    Po zakończonych półfinałach, administrator ligi w zakładce `wyniki->playoffs->kolejka 2` będzie miał dostępny guzik `Generuj terminarz dla fazy play off (finał)`, który automatycznie przygotuje terminarz dla fazy finałowej i meczu o 3 miejsce.

-   dokonaj konfiguracji draftu w tabeli megaliga_draft_data
    a) playoff_draft_window_open = 1 – udostepnij formularz do draftowania zawodnikow; 0 – ukryj formularz draftu
    b) playoff_draft_credit_enabled = 1 – draft uwzględnia wartość zawodników i dostępny kredyt gracza. W formularzu draftu, na liście wyboru zawodników pojawią się tylko Ci zawodnicy, na których stać będzie danego gracza; 0 – draft nie uwzglednia wartości zawodników (wartość domyślna)
-   w panelu administracyjnym w zakładce: WPTables najeżdżamy myszką na tabele „Draft Table – Playoff” i wybieramy opcje „edit”. W zależności od wybranej opcji „playoff_draft_credit_enabled” zaznaczamy/odnzaczamy checkbox przy polu „Cena”. To spowoduje pojawienie/ukrycie się kolumny Cena w tabeli z zawodnikami do draftu w zakładce „Draft->Play-off”
-   po zakończonym drafcie ustawiamy w tabeli megaliga_draft_data pole playoff_draft_window_open na wartość 0 (ukrycie formularza).
-   formularz widoczny jest w danym momencie, tylko dla gracza, którego kolej wypada. Po wybraniu zawodnika lub spasowaniu, system udostępnia formularz następnemu graczowi w kolejności
-   gracz może ominąć kolejkę i nie wybierać zawodnika poprzez naciśnięcie przyciku „Pas”.

20. Przygotowanie Grand Prix

    a) Wpisz wyniki z tabeli GP do tabeli `megaliga_grandprix_history`

    b) Dokonaj aktualizacji zawodników biorących udział w realnym GP w tabeli `megaliga_grandprix_players`

    c) Uzupełnij tabelę `megaliga_grandprix_bet_status` 11 kolejkami i ustaw wrtość `is_open` == 1

    d) Dodaj nowy sezon GP do tabeli `megaliga_grandprix_season`:

    -   w polu „season_name” wprowadź rok, w którym odbywa się dana edycja megaligi
    -   w polu „current” wpisz jeden dla obecnie rozgrywanego sezonu. Jednocześnie dla wszystkich pozostałych wpisów odpowiadającym poprzednim sezonom w pole „current” wpisz wartość = 0

    c) Wpisz do `megaliga_grandprix_history_champion` mistrza GP z kończącego się sezonu

    -   user_name - wp_users.display_name

    d) Wyczyść rekordy z następujących tabel:

    -   megaliga_grandprix_bets (AUTO_INCREMENT = 1)
    -   megaliga_grandprix_results (AUTO_INCREMENT = 1)

    e) Wpisz aktualne daty kolejek w tabeli `megaliga_grandprix_round_calendar`

Tests:

1. set dolce teams
   UPDATE `megaliga_user_data` SET `ligue_groups_id`= 1 WHERE team_names_id IN (4,19,17,12,5,13)

2. set gabbana teams
   UPDATE `megaliga_user_data` SET `ligue_groups_id`= 2 WHERE team_names_id IN (21, 18,16,6,3,20)

3. set 1st round draft lottery order dolce teams
   UPDATE `megaliga_1round_draft_order_lottery_outcome` SET `one`= 46,`two`= 44,`three`= 40, `four`= 27, `five`= 52, `six`= 54 WHERE id_draft_order_lottery_outcome = 1

4. set 1st round draft lottery order gabbana teams
   UPDATE `megaliga_1round_draft_order_lottery_outcome` SET `one`= 26,`two`= 20,`three`= 51, `four`= 55, `five`= 56, `six`= 47 WHERE id_draft_order_lottery_outcome = 2

5. reset megaliga_players table
   UPDATE `megaliga_players` SET `id_user_playoff`= NULL, drafted_with_number_dolce= NULL, `drafted_with_number_playoff`=NULL
   Przepraszamy, megaliga jest chwilowo niedostępna. Trwają prace przygotowawcze do nowego sezonu 2021
