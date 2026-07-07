import { Controller } from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["input", "icon"]

    toggle() {
        const isPassword = this.inputTarget.type === "password"
        this.inputTarget.type = isPassword ? "text" : "password"
        // Swap les deux icônes
        this.iconTargets.forEach(icon => icon.classList.toggle("hidden"))
    }
}
