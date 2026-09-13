import type { NavProps } from "./types";
import styles from './Sidebar.module.css';

function Nav({ texts }: NavProps) {
    return (
        <nav className={styles['sidebar-nav']}>
            {texts.item.map((item) => (
                <a className={styles['sidebar-nav__link']} key={item.link} href={item.link}>
                    <div className={styles['sidebar-nav__dot']}></div>
                    <span className={styles['sidebar-nav__text']}>{item.label}</span>
                </a>
            ))}
        </nav>
    )
}

export default Nav;