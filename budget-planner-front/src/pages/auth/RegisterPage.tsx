// pages/auth/RegisterPage.tsx
import { useState } from 'react';
import type { FormEvent } from 'react';
import { Link, useNavigate } from 'react-router';
import { Main } from '../../components/layout/Main';
import { AuthAside } from '../../components/layout/AuthAside';
import { HouseholdLegend } from '../../components/layout/HouseholdLegend';
import { HOUSEHOLD_MEMBERS } from '../../components/layout/HouseholdLegend/texts';
import { TextField, Button, FormError } from '../../components/ui';
import useAsyncSubmit from '../../hooks/useAsyncSubmit';
import { register } from '../../services/auth';
import styles from './AuthPage.module.css';

function RegisterPage() {
  const navigate = useNavigate();
  const [firstName, setFirstName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [invitationCode, setInvitationCode] = useState('');
  const { isSubmitting, errorMessage, submit } = useAsyncSubmit();

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();

    submit(async function registerAndRedirect() {
      await register({ firstName, email, password, invitationCode });
      navigate('/dashboard');
    });
  }

  return (
    <Main variant="auth">
      <AuthAside
        headline="Rejoignez le foyer : chacun garde son compte, tout le monde garde la vue d'ensemble."
        decoration={<HouseholdLegend members={HOUSEHOLD_MEMBERS}/>}
        footerText="Foyer Diano"
      />

      <section className={styles['auth-page__form-section']}>
        <div className={styles['auth-page__form-wrapper']}>
          <h1 className={styles['auth-page__title']}>Créer un compte</h1>
          <p className={styles['auth-page__subtitle']}>Rejoignez le foyer Diano sur budget-planner.</p>

          <form className={styles['auth-page__form']} onSubmit={handleSubmit}>
            <TextField
              id="firstName"
              label="Prénom"
              type="text"
              value={firstName}
              placeholder="Céline"
              autoComplete="given-name"
              onChange={setFirstName}
            />
            <TextField
              id="email"
              label="Adresse e-mail"
              type="email"
              value={email}
              placeholder="celine.diano@gmail.com"
              autoComplete="email"
              onChange={setEmail}
            />
            <TextField
              id="password"
              label="Mot de passe"
              type="password"
              value={password}
              placeholder="••••••••••"
              autoComplete="new-password"
              onChange={setPassword}
            />
            <TextField
              id="invitationCode"
              label="Code d'invitation du foyer"
              type="text"
              value={invitationCode}
              placeholder="DIANO-2026"
              autoComplete="off"
              onChange={setInvitationCode}
            />

            <FormError message={errorMessage}/>

            <Button type="submit" isLoading={isSubmitting} loadingLabel="Création du compte...">
              Créer mon compte
            </Button>
          </form>

          <p className={styles['auth-page__switch']}>
            Déjà un compte ? <Link className={styles['auth-page__switch-link']} to="/login">Se connecter</Link>
          </p>
        </div>
      </section>
    </Main>
  );
}

export default RegisterPage;
