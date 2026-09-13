import Brand from "./Brand";
import Nav from "./Nav";
import styles from './Sidebar.module.css';
import { SIDEBAR } from "./texts";

function Sidebar() {
    return (
        <div className={styles.sidebar}>
            <Brand texts={SIDEBAR.brand}/>
            <Nav texts={SIDEBAR.nav}/>
        </div>
    )
}

export default Sidebar;