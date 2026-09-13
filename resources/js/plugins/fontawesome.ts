import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Only the specific icons actually used across the app are imported here.
// Importing the full `fas`/`far`/`fab` packs (as before) pulls in every icon
// in each set — thousands of unused SVGs — and was the main contributor to
// the ~2MB main bundle. Add new icons to both the import and library.add()
// below when a new `fa-solid fa-*` name is introduced in a page/component.
import {
    faAnglesLeft, faAnglesRight, faArrowLeft, faArrowRight, faArrowTrendDown, faArrowTrendUp, faArrowUpRightFromSquare, faBars, faBell, faBolt, faBox,
    faCalendarXmark, faCamera, faCartShopping, faCheck, faChevronDown, faChevronLeft, faChevronRight,
    faCircleCheck, faCircleDown, faCircleInfo, faCloudArrowUp, faCommentDots, faComments, faCreditCard, faDownload, faEdit,
    faEnvelope, faEye, faEyeSlash, faHourglassHalf, faIdCard, faImage, faInbox,
    faLayerGroup, faLink, faLocationCrosshairs, faLocationDot, faLock, faMagnifyingGlass, faMessage, faMinus,
    faMobileScreen, faMoneyBillWave, faNewspaper, faPaperPlane, faPhone, faPlus, faPlusCircle, faPrint, faQrcode, faReceipt, faRightFromBracket,
    faRotateLeft, faShieldHalved, faShirt, faShoppingBasket, faSliders, faSpinner, faSprayCanSparkles, faStar, faTachographDigital, faThumbsUp,
    faTrash, faTriangleExclamation, faTruck, faTruckFast, faTshirt, faUpload, faUserCircle,
    faUserPlus, faUsers, faUserTie, faWallet, faXmark, faXmarkCircle,
} from '@fortawesome/free-solid-svg-icons'
import { faCopy } from '@fortawesome/free-regular-svg-icons'

library.add(
    faAnglesLeft, faAnglesRight, faArrowLeft, faArrowRight, faArrowTrendDown, faArrowTrendUp, faArrowUpRightFromSquare, faBars, faBell, faBolt, faBox,
    faCalendarXmark, faCamera, faCartShopping, faCheck, faChevronDown, faChevronLeft, faChevronRight,
    faCircleCheck, faCircleDown, faCircleInfo, faCloudArrowUp, faCommentDots, faComments, faCreditCard, faDownload, faEdit,
    faEnvelope, faEye, faEyeSlash, faHourglassHalf, faIdCard, faImage, faInbox,
    faLayerGroup, faLink, faLocationCrosshairs, faLocationDot, faLock, faMagnifyingGlass, faMessage, faMinus,
    faMobileScreen, faMoneyBillWave, faNewspaper, faPaperPlane, faPhone, faPlus, faPlusCircle, faPrint, faQrcode, faReceipt, faRightFromBracket,
    faRotateLeft, faShieldHalved, faShirt, faShoppingBasket, faSliders, faSpinner, faSprayCanSparkles, faStar, faTachographDigital, faThumbsUp,
    faTrash, faTriangleExclamation, faTruck, faTruckFast, faTshirt, faUpload, faUserCircle,
    faUserPlus, faUsers, faUserTie, faWallet, faXmark, faXmarkCircle,
    faCopy,
)

export { FontAwesomeIcon }
