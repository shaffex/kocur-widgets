//
//  ContentView.swift
//  Remote Widget
//
//  Created by Peter Popovec on 02/04/2026.
//

import SwiftUI
import MagicUI

struct ContentView: View {
    init() {
        // The chat/status history rows rendered by the remaining MagicUI
        // fragments use this modifier to make the whole row tappable.
        ModifierRegistry.shared.register(Modifier_contentShapeRect(), for: "contentShapeRect")
    }

    var body: some View {
        MainView()
    }
}

#Preview {
    ContentView()
}
