Structure de dossiers recommandée<br/>
src/ <br/>
├── assets/              # images, fonts, icônes<br/>
├── components/          # composants réutilisables (UI pure)<br/>
│   ├── ui/              # boutons, inputs, cards... (Atomic Design "atoms/molecules")<br/>
│   └── layout/          # Header, Sidebar, Footer...<br/>
├── features/            # organisation par domaine métier (feature-based)<br/>
│   ├── budget/<br/>
│   │   ├── components/  # composants spécifiques à cette feature<br/>
│   │   ├── hooks/       # hooks métier (useBudget, useTransactions...)<br/>
│   │   ├── api/         # appels API liés au budget<br/>
│   │   ├── types/       # types TS spécifiques<br/>
│   │   └── utils/<br/>
│   └── auth/<br/>
├── hooks/               # hooks génériques réutilisables (useDebounce, useFetch...)<br/>
├── lib/ ou services/    # config axios/fetch, client API, wrappers externes<br/>
├── pages/ ou routes/    # composants de page, un par route<br/>
├── store/               # state global (Zustand, Redux, Context...)<br/>
├── types/               # types globaux partagés<br/>
├── utils/               # fonctions utilitaires génériques<br/>
├── constants/           # constantes globales<br/>
└── App.tsx<br/>