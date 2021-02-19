megaliga – instrukcja obsługi v.4.0

1. Wprowadź wszystkich graczy do tabeli megaliga_user_data

2. Ustaw następujące wartości w kolumnach megaliga_user_data:
   reached_playoff = 0
   player_draft_number = 1
   player_draft_number_playoff = 1
   (jeżeli wykorzystywane) w kolumnie credit_balance i credit_balance_playoff wstaw wartość kredytu przyznawanego dla każdego gracza
   ligue_groups_id = 4 – reset przydziału do grupy

3. W tabeli megaliga_draft_data wstaw:

-   1 w kolumnie draft_current_round_dolce
-   1 w kolumnie draft_current_round_gabbana
-   1 w kolumnie playoff_draft_current_round
-   1 w kolumnie player_draft_number
-   1 w kolumnie player_draft_number_playoff

4. W tabeli megaliga_1round_draft_order_lottery_outcome wpisz 0 w kolumnach one, two, three, four, five, six dla obu wierszy reprezentujących grupę dolce i gabbana

5. Uruchom losowanie przydziału do grup w widoku "Zespoły" poprzez ustawienie w kolumnie group_lottery_open.megaliga_draft_data = 1

6. Kolejność wyboru w drafcie w fazie playoff generuje się automatycznie

7. Dokonaj aktualizacji tabeli megaliga_players

-   usuń zawodników nie istniejących
-   dodaj nowych zawodników
-   dla każdego zawodnika uzupełnij pola credit (wartość zawodnika w drafcie do sezonu zasadniczego) i credit_playoff (pole opcjonalne; wartość zawodnika w drafcie do playoff)
-   usuń przypisanie do graczy w rundzie zasadniczej i w fazie playoff
-   usuń numer w drafcie, do sezonu zasadniczego i playoff, z którym zostali wybrani do zespołu

8. Dodaj nowy sezon megaligi do tabeli megaliga_season

-   w polu „season_name” wprowadź rok, w którym odbywa się dana edycja megaligi
-   w polu „number_of_groups” podaj liczbę grup w danym sezonie megaligi
-   w polu „current” wpisz jeden dla obecnie rozgrywanego sezonu. Jednocześnie dla wszystkich pozostałych wpisów odpowiadającym poprzednim sezonom w pole „current” wpisz wartość = 0

9. Jeżeli w danym sezonie megaliga dodane są nowe grupy to dodaj je do tabeli megaliga_ligue_groups (to ma wpływ na użyte szablony stron; zmiany grup wymagaja implementacji zmian w kodzie)

10. Draft do rundy zasadniczej

-   dokonaj konfiguracji draftu w tabeli megaliga_draft_data
    a) draft_window_open = 1 – udostepnij formularz do draftowania zawodnikow; 0 – ukryj formularz draftu
    b) draft_credit_enabled = 1 – draft uwzględnia wartość zawodników i dostępny kredyt gracza. W formularzu draftu, na liście wyboru zawodników pojawią się tylko Ci zawodnicy, na których stać będzie danego gracza; 0 – draft nie uwzglednia wartości zawodników
    c) wejdź do widoku "draft->kolejność wyboru->runda zasadnicza" aby załadować tabelę z kolejnością wyboru
-   w panelu administracyjnym w zakładce: WPTables najeżdżamy myszką na tabele „Draft Table – Regular Season” i wybieramy opcje „edit”. W zależności od wybranej opcji draft_credit_enabled zaznaczamy/odnzaczamy checkbox przy polu „Cena”. To spowoduje pojawienie/ukrycie się kolumny Cena w tabeli z zawodnikami do draftu w zakładce „Draft->Runda Zasadnicza”
-   po zakończonym drafcie ustawiamy w tabeli megaliga_draft_data pole draft_window_open na wartość 0 (ukrycie formularza).
-   formularz widoczny jest w danym momencie, tylko dla gracza, którego kolej wypada. Po wybraniu zawodnika lub spasowaniu, system udostępnia formularz następnemu graczowi w kolejności
-   gracz może ominąć kolejkę i nie wybierać zawodnika poprzez naciśnięcie przyciku „Pas”.

11. Wprowadź rozpiskę meczy dla każdej z 14 kolejek rundy zasadniczej do tabeli megaliga_schedule

-   po 7 kolejkach rozpoczyna się runda rewanżowa, gdzie zwycięzca dwumeczu otrzymuje dodatkowy punkt
-   w tabeli megaliga_schedule w kolumnie id_rematch_schedule dla kolejek rewanżowych podaj id_schedule pierwszego spotkania
-   każda drużyna rozgrywa mecz i rewanż z każdą z pozostałych 7 drużyn. Zwycięzca każdego z pojedynków ( liczy się suma małych punktów) otrzymuję w dodatkowy punkt po rozegranym meczu rundy rewanżowej

12. Wyczyść rekordy z następujących tabel:

-   megaliga_playoff_ladder
-   megaliga_schedule_playoff
-   megaliga_schedule
-   megaliga_scores
-   megaliga_scores_playoff
-   megaliga_starting_lineup
-   megaliga_starting_lineup_playoff
-   megaliga_trainer_score
-   megaliga_trainer_score_playoff
-   megaliga_playoff_draft_order

13. Przygotowanie fazy playoff

-   w tabeli megaliga_user_data oznacz drużyny, które awansowały do fazy playoff poprzez ustawienie wartości 1 w polu „reached_playoff”
-   dokonaj konfiguracji draftu w tabeli megaliga_draft_data
    a) playoff_draft_window_open = 1 – udostepnij formularz do draftowania zawodnikow; 0 – ukryj formularz draftu
    b) playoff_draft_credit_enabled = 1 – draft uwzględnia wartość zawodników i dostępny kredyt gracza. W formularzu draftu, na liście wyboru zawodników pojawią się tylko Ci zawodnicy, na których stać będzie danego gracza; 0 – draft nie uwzglednia wartości zawodników (wartość domyślna)
-   w panelu administracyjnym w zakładce: WPTables najeżdżamy myszką na tabele „Draft Table – Playoff” i wybieramy opcje „edit”. W zależności od wybranej opcji „playoff_draft_credit_enabled” zaznaczamy/odnzaczamy checkbox przy polu „Cena”. To spowoduje pojawienie/ukrycie się kolumny Cena w tabeli z zawodnikami do draftu w zakładce „Draft->Play-off”
-   po zakończonym drafcie ustawiamy w tabeli megaliga_draft_data pole playoff_draft_window_open na wartość 0 (ukrycie formularza).
-   formularz widoczny jest w danym momencie, tylko dla gracza, którego kolej wypada. Po wybraniu zawodnika lub spasowaniu, system udostępnia formularz następnemu graczowi w kolejności
-   gracz może ominąć kolejkę i nie wybierać zawodnika poprzez naciśnięcie przyciku „Pas”.

14. Wprowadź rozpiskę meczy dla fazy playoff w tabeli megaliga_schedule_playoff

-   należy pamiętać, że id gracza zapisane w polu np.: id_user_team1 dla rundy 1 musi być również zapisane w tym samym polu dla rundy 2.
-   tabele uzupełnia się na bierząco. Najpierw dla fazy półfinałowej mecze 1 i 2 rundy, potem, gdy znane będą pary finałowe i meczu o 3 miejsce, wprowadzamy kolejne mecze.

Przykład:
id_schedule id_user_team1 id_user_team2 round_number team1_score team2_score
1, 10, 12, 1, NULL, NULL
2, 10, 12, 2, NULL, NULL

15. Uzupełnij tabele megaliga_playoff_ladder w celu wyświeltenia danych w zakładce tabela->play-off

-   każdy rekord tej tabeli opisuje pare drużyn grających ze sobą w danej fazie playoff:
    a) półfinał (semifinal)
    b) finał (final)
    c) mecz o 3 miejsce (3rdplace)
-   w każdej fazie playoff rozgrywane są 2 rundy
-   tabele uzupełnia się na bierząco w trakcie trwania playoff (nie znane są pary finałowe i meczu o 3 miejsce po zakończeniu rundy zasadniczej)
-   w fazie półfinałowej pary tworzy się na podstawie miejsca zajętego przez drużyny w sezonie zasadniczym:
    a) 1 z 4 i 2 z 3

16. Przykład uzupełnienia tabeli megaliga_playoff_ladder
    id_playoff_ladder id_user_team1 id_user_team2 stage id_schedule_round1 id_schedule_round2 seed_number_team1 seed_number_team2
    1, 10, 12, semifinal, 1, 2, 1, 4

-   id_user_team1/2 – ta sama wartość id gracza jak zapisana w tabeli megaliga_schedule_playoff
-   stage – semifinal|final|3rdplace
-   id_schedule_round1/2 – id_schedule z tabeli megaliga_schedule_playoff wskazujący na rekord reprezentujący mecz dla rundy odpowiednio 1/2 dla tej pary w danej fazie playoff
-   seed_number_team1/2 miejsce jakie dana drużyna zajmowała po zakończeniu sezonu zasadniczego (tzw. numer rozstawienia)

Tests:

1. set dolce teams
   UPDATE `megaliga_user_data` SET `ligue_groups_id`= 1 WHERE team_names_id IN (4,19,17,12,5,13)

2. set gabbana teams
   UPDATE `megaliga_user_data` SET `ligue_groups_id`= 2 WHERE team_names_id IN (21, 18,16,6,3,20)
