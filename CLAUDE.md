# CLAUDE.md

Ce fichier donne à Claude Code le contexte et les règles à suivre pour travailler sur ce projet.

## Contexte du projet

**budget-planner** — application web de gestion de budget personnel/familial (projet portfolio).

- **Frontend** : `budget-planner-front` — React + TypeScript + Vite
- **Backend** : Symfony / PHP, base MySQL
- Fonctionnalités principales : tableau de bord (répartition en camembert, évolution mensuelle), gestion multi-comptes bancaires, dépenses, revenus, simulation règle 50/30/20, foyer partagé (accès lecture seule pour un proche), paramètres.
- Maquettes Figma disponibles pour l'ensemble de l'application.

## Philosophie de code

Le code doit rester **simple, explicite et facile à relire**, même si ça veut dire écrire quelques lignes de plus. On privilégie la clarté immédiate à l'élégance condensée.

### Règles strictes

1. **Pas de fonctions fléchées (arrow functions) pour les déclarations de fonction**
   - Utiliser `function nomDeLaFonction() { ... }` plutôt que `const nomDeLaFonction = () => { ... }`
   - Ça vaut pour les fonctions "métier", les handlers, les fonctions utilitaires.
   - Exception tolérée : callbacks très courts et jetables inline (ex. `.map()`) si aucune alternative claire — mais dès que la logique dépasse une ligne triviale, en faire une vraie fonction nommée.

2. **Pas de ternaires**
   - Utiliser des blocs `if / else` complets plutôt que `condition ? a : b`.
   - Même pour des cas simples (assignation conditionnelle, rendu JSX conditionnel) : préférer une variable calculée en amont via `if / else`, ou une fonction dédiée, plutôt qu'un ternaire inline.

3. **Logique entière et lisible, pas de raccourcis condensés**
   - Éviter le chaînage excessif (optional chaining en cascade, enchaînements `.filter().map().reduce()` illisibles) si une version en plusieurs étapes nommées est plus claire.
   - Préférer des conditions explicites (`if (valeur === undefined)`) aux raccourcis implicites (`if (!valeur)`) quand la nuance compte.
   - Nommer les variables intermédiaires plutôt que d'empiler des expressions.

4. **Code bien factorisé**
   - Pas de duplication : extraire toute logique répétée dans une fonction ou un hook réutilisable.
   - Une fonction = une responsabilité claire. Découper les fonctions trop longues.
   - Regrouper la logique métier réutilisable dans des fonctions utilitaires (`utils/`) ou des hooks custom (`hooks/`) plutôt que de la dupliquer dans les composants.
   - Nommage explicite en français ou anglais (rester cohérent avec le reste du fichier/dossier concerné).

### Exemple attendu

```tsx
// ❌ À éviter
const getStatusLabel = (status) => status === "ok" ? "Validé" : "En attente";

// ✅ Attendu
function getStatusLabel(status) {
  if (status === "ok") {
    return "Validé";
  }
  return "En attente";
}
```

```tsx
// ❌ À éviter
{isLoading ? <Spinner /> : <Dashboard data={data} />}

// ✅ Attendu
function renderContent() {
  if (isLoading) {
    return <Spinner />;
  }
  return <Dashboard data={data} />;
}
```

## Stack technique

- React + TypeScript, Vite
- Symfony / PHP, MySQL côté backend
- Docker pour l'environnement de dev (backend)

## Conventions générales

- TypeScript : typer explicitement les props, les retours de fonction et les données API (pas de `any` implicite).
- Organisation des composants : à préciser selon la structure du repo (Atomic Design si applicable).
- Avant de proposer une refacto ou un nouveau composant, vérifier s'il existe déjà une fonction/hook réutilisable équivalent.

## Ce que Claude doit faire

- Toujours respecter les 4 règles strictes ci-dessus dans tout code généré ou modifié.
- Si une de ces règles rend une portion de code nettement plus lourde sans bénéfice de lisibilité, le signaler plutôt que de l'appliquer aveuglément.
- Proposer une factorisation quand une logique similaire apparaît à plusieurs endroits.

- Prendre en compte des variables et de la maquette réaliser sur Figma.