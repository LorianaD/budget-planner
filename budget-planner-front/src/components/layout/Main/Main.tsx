// Main/Main.tsx
import type { MainProps } from "./types";
import styles from './Main.module.css';

function Main({ variant, children }: MainProps) {
  return (
    <main className={`${styles.main} ${styles[`main--${variant}`]}`}>
      {children}
    </main>
  );
}

export default Main;