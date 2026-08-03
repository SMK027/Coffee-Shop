#!/usr/bin/env bash
set -e

MOBILE_DIR="$(cd "$(dirname "$0")" && pwd)"
ICON="$MOBILE_DIR/assets/icon.png"

# ── Vérifications préalables ─────────────────────────────────────────
if [ ! -f "$ICON" ]; then
  echo "❌  Icône introuvable : $ICON"
  echo "   → Enregistre d'abord l'image dans assets/icon.png puis relance ce script."
  exit 1
fi

echo "✅  Icône trouvée : $(python3 -c "from PIL import Image; img=Image.open('$ICON'); print(img.size)")"

# ── local.properties ────────────────────────────────────────────────
ANDROID_SDK="${ANDROID_HOME:-/home/smk/Android/Sdk}"
echo "sdk.dir=$ANDROID_SDK" > "$MOBILE_DIR/android/local.properties"
echo "✅  local.properties → sdk.dir=$ANDROID_SDK"

# ── Régénération des icônes natives via prebuild ─────────────────────
echo ""
echo "🔄  Régénération des icônes Android (expo prebuild)…"
cd "$MOBILE_DIR"
npx expo prebuild --platform android --no-install 2>&1 | grep -E "(icon|Icon|error|Error|warn|WARN|✓|✗)" || true

# ── Build APK debug (rapide, signable en dev) ─────────────────────────
echo ""
echo "🔨  Build APK en cours (assembleDebug)…"
cd "$MOBILE_DIR/android"
./gradlew assembleDebug --no-daemon 2>&1 | tail -20

APK=$(find "$MOBILE_DIR/android/app/build/outputs/apk/debug" -name "*.apk" 2>/dev/null | head -1)

if [ -n "$APK" ]; then
  echo ""
  echo "✅  APK généré :"
  echo "   $APK"
  cp "$APK" "$MOBILE_DIR/CoffeeShop-debug.apk"
  echo "   → Copié dans : $MOBILE_DIR/CoffeeShop-debug.apk"
else
  echo "❌  APK introuvable — consulte les logs ci-dessus."
  exit 1
fi
