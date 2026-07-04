//
//  AppState.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import Foundation
import Combine

/// Native, cross-screen ephemeral state that has nothing to do with the
/// MagicUI-rendered remote fragments (unlike `AppEnvironment.env`). Holds the
/// bits that need to outlive a dismissed sheet or trigger a refresh in a
/// different tab.
@MainActor
final class AppState: ObservableObject {
    static let shared = AppState()
    private init() {}

    @Published var messageError: String?
    @Published var chatReloadToken = UUID()
    @Published var widgetPreviewReloadToken = UUID()
}
