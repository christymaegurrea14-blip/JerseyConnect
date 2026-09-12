import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Only the specific icons actually used across the app are imported here.
// Importing the full `fas`/`far`/`fab` packs (as before) pulls in every icon
// in each set — thousands of unused SVGs — and was the main contributor to
// the ~2MB main bundle. Add new icons to both the import and library.add()
// below when a new `fa-solid fa-*` name is introduced in a page/component.
import {
    faAnglesLeft, faAnglesRight, faArrowRight, faArrowTrendDown, faArrowTrendUp, faArrowUpRightFromSquare, faBars, faBox,
    faCalendarXmark, faCartShopping, faCheck, faChevronDown, faChevronLeft, faChevronRight,
    faCircleCheck, faCircleDown, faCircleInfo, faCloudArrowUp, faComments, faCreditCard, faEdit,
    faEnvelope, faEye, faHourglassHalf, faIdCard, faImage, faInbox,
    faLink, faLocationCrosshairs, faLocationDot, faLock, faMagnifyingGlass, faMessage,
    faMoneyBillWave, faNewspaper, faPaperPlane, faPlusCircle, faReceipt, faRightFromBracket,
    faShirt, faShoppingBasket, faSpinner, faSprayCanSparkles, faTachographDigital, faThumbsUp,
    faTrash, faTriangleExclamation, faTruck, faTruckFast, faTshirt, faUpload, faUserCircle,
    faUserPlus, faUsers, faUserTie, faWallet, faXmark, faXmarkCircle,
} from '@fortawesome/free-solid-svg-icons'

library.add(
    faAnglesLeft, faAnglesRight, faArrowRight, faArrowTrendDown, faArrowTrendUp, faArrowUpRightFromSquare, faBars, faBox,
    faCalendarXmark, faCartShopping, faCheck, faChevronDown, faChevronLeft, faChevronRight,
    faCircleCheck, faCircleDown, faCircleInfo, faCloudArrowUp, faComments, faCreditCard, faEdit,
    faEnvelope, faEye, faHourglassHalf, faIdCard, faImage, faInbox,
    faLink, faLocationCrosshairs, faLocationDot, faLock, faMagnifyingGlass, faMessage,
    faMoneyBillWave, faNewspaper, faPaperPlane, faPlusCircle, faReceipt, faRightFromBracket,
    faShirt, faShoppingBasket, faSpinner, faSprayCanSparkles, faTachographDigital, faThumbsUp,
    faTrash, faTriangleExclamation, faTruck, faTruckFast, faTshirt, faUpload, faUserCircle,
    faUserPlus, faUsers, faUserTie, faWallet, faXmark, faXmarkCircle,
)

export { FontAwesomeIcon }
