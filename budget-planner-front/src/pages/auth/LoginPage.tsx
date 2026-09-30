// pages/auth/LoginPage.tsx
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Link, useNavigate } from 'react-router';
import { Main } from '../../components/layout/Main';
import { AuthAside } from '../../components/layout/AuthAside';
import { BudgetDonut } from '../../components/layout/BudgetDonut';
import { TextField, Button, FormError } from '../../components/ui';
import useAsyncSubmit from '../../hooks/useAsyncSubmit';
import { login } from '../../services/auth';
import styles from './AuthPage.module.css';

function LoginPage() {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const { isSubmitting, errorMessage, submit } = useAsyncSubmit();

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    submit(async function loginAndRedirect() {
      await login({ email, password });
      navigate('/dashboard');
    });
  }

  return (
    <Main variant="auth">
      <AuthAside
        headline="Gardez le foyer aligné sur la règle des 50/30/20, à plusieurs comptes et à plusieurs mains."
        decoration={<BudgetDonut/>}
        footerText="Foyer Diano · Loriana, Céline & Papa"
      />

      <section className={styles['auth-page__form-section']}>
        <div className={styles['auth-page__form-wrapper']}>
          <h1 className={styles['auth-page__title']}>Bon retour</h1>
          <p className={styles['auth-page__subtitle']}>Connectez-vous pour retrouver le budget du foyer.</p>

          <form className={styles['auth-page__form']} onSubmit={handleSubmit}>
            <TextField
              id="email"
              label="Adresse e-mail"
              type="email"
              value={email}
              placeholder="loriana.diano@gmail.com"
              autoComplete="email"
              onChange={setEmail}
            />
            <TextField
              id="password"
              label="Mot de passe"
              type="password"
              value={password}
              placeholder="••••••••••"
              autoComplete="current-password"
              onChange={setPassword}
            />

            <span className={styles['auth-page__forgot-password']}>Mot de passe oublié ?</span>

            <FormError message={errorMessage}/>

            <Button type="submit" isLoading={isSubmitting} loadingLabel="Connexion...">
              Se connecter
            </Button>
          </form>

          <p className={styles['auth-page__switch']}>
            Pas encore de compte ? <Link className={styles['auth-page__switch-link']} to="/register">Créer un compte</Link>
          </p>
        </div>
      </section>
    </Main>
  );
}

export default LoginPage;
