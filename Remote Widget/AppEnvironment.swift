//
//  AppEnvironment.swift
//  Remote Widget
//
//  Created by Peter Popovec on 04/07/2026.
//

import MagicUI

/// The single `MagicEnvironment` shared between native SwiftUI screens and
/// the handful of MagicUI-rendered remote fragments (chat/status history,
/// widget preview) so actions embedded in that remote XML can talk to native
/// state (and vice versa).
enum AppEnvironment {
    static let env = MagicEnvironment(persistenceKey: "magicui.demo")
}
