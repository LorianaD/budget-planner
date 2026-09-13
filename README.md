# budget-planner

Application web de suivi et de simulation budgétaire familiale, développée en Symfony (API) + React, dans une logique de projet personnel/portfolio.

## Contexte

Remplace un suivi jusqu'ici géré via des tableurs Excel (comptes multiples, catégories de dépenses, abonnements, répartition 50/30/20). L'objectif est d'ajouter ce que le tableur ne permet pas facilement : partage en lecture avec un second utilisateur, et simulation de scénarios sans toucher aux données réelles.

## Fonctionnalités

- Authentification et gestion de foyer (un administrateur, un ou plusieurs lecteurs)
- Comptes multiples par foyer (ex: compte familial, compte individuel)
- Catégories et sous-catégories hiérarchiques de dépenses/revenus
- Transactions récurrentes (abonnements, loyer, assurances) avec date de fin d'engagement
- Tableau de bord : soldes, répartition par catégorie, évolution mensuelle/annuelle
- Règle 50/30/20 : cible personnalisable (essentiel / loisirs / épargne) comparée automatiquement au réel
- Module de simulation : scénarios de dépenses hypothétiques (changement de loyer, résiliation d'abonnement...) comparés au réel, y compris leur impact sur le 50/30/20

## Stack technique

| Couche | Techno |
|---|---|
| Backend | Symfony 7 (API Platform ou API REST) |
| Base de données | MySQL |
| Authentification | JWT (LexikJWTAuthenticationBundle) |
| Frontend | React |
| Graphiques | Recharts / Chart.js |
| Hébergement | O2Switch |

## Modèle de données

Voir le cahier des charges du projet pour le MCD/MLD complet (entités : `Utilisateur`, `Foyer`, `MembreFoyer`, `Compte`, `Categorie`, `Transaction`, `Scenario`, `ScenarioTransaction`, `BudgetAllocation`).

## Roadmap

1. Modélisation base de données + API Symfony
2. Authentification et gestion multi-utilisateur/foyer
3. Frontend React : saisie et vues mensuelle/annuelle
4. Tableau de bord et graphiques
5. Module de simulation et 50/30/20
6. Partage effectif avec un lecteur externe (retours d'usage réel)

## Auteur

Loriana Diano — projet réalisé dans le cadre de mon parcours de développeuse web (LaPlateforme_).
