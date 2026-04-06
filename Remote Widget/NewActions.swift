//
//  NewActions.swift
//  MagicWidget
//
//  Created by Peter Popovec on 29/03/2026.
//

import SwiftUI
import MagicUiFramework
import WidgetKit
import AppIntents
import ActivityKit

struct SxAction_reloadAllTimelines: SxActionProtocol {
    let node: MagicNode?
    
    func execute(_ actionString: String) {
        WidgetCenter.shared.reloadAllTimelines()
    }
}

struct SxAction_startLiveActivity: SxActionProtocol {
    let node: MagicNode?
    
    func fetchXML(from urlString: String) async throws -> String {
        guard let url = URL(string: urlString) else {
            throw URLError(.badURL)
        }
        
        let (data, response) = try await URLSession.shared.data(from: url)
        
        guard let httpResponse = response as? HTTPURLResponse,
              httpResponse.statusCode == 200 else {
            throw URLError(.badServerResponse)
        }
        
        guard let xml = String(data: data, encoding: .utf8) else {
            throw URLError(.cannotDecodeContentData)
        }
        
        return xml
    }
    
    func execute(_ actionString: String) {
        Task { @MainActor in
            let authorizationInfo = ActivityAuthorizationInfo()
            print("Live Activity enabled:", authorizationInfo.areActivitiesEnabled)

            do {
                let activity = try Activity<ActivityData>.request(
                    attributes: ActivityData(),
                    content: .init(state: .init(xml: "<body><circle/></body>"), staleDate: nil)
                )
                print("Live Activity started:", activity.id)
                
                let xml = try await fetchXML(from: "https://magic-ui.com/KumWidgets/data/petres_liveactivity.xml")
                await activity.update(using: ActivityData.ContentState(xml: xml))
                
            } catch {
                print("Live Activity start failed:", error.localizedDescription)
                print("Live Activity error:", String(describing: error))
            }
        }
    }
}

struct SxAction_updateLiveActivity: SxActionProtocol {
    let node: MagicNode?
    
    func fetchXML(from urlString: String) async throws -> String {
        guard let url = URL(string: urlString) else {
            throw URLError(.badURL)
        }
        
        let (data, response) = try await URLSession.shared.data(from: url)
        
        guard let httpResponse = response as? HTTPURLResponse,
              httpResponse.statusCode == 200 else {
            throw URLError(.badServerResponse)
        }
        
        guard let xml = String(data: data, encoding: .utf8) else {
            throw URLError(.cannotDecodeContentData)
        }
        
        return xml
    }
    
    func execute(_ actionString: String) {
        Task { @MainActor in
            let authorizationInfo = ActivityAuthorizationInfo()
            print("Live Activity enabled:", authorizationInfo.areActivitiesEnabled)

            do {
                let activity = try Activity<ActivityData>.request(
                    attributes: ActivityData(),
                    content: .init(state: .init(xml: "<body><circle/></body>"), staleDate: nil)
                )
                print("Live Activity started:", activity.id)
                
                let xml = try await fetchXML(from: "https://magic-ui.com/KumWidgets/petres.php?family=systemMedium")
                await activity.update(using: ActivityData.ContentState(xml: xml))
                
            } catch {
                print("Live Activity start failed:", error.localizedDescription)
                print("Live Activity error:", String(describing: error))
            }
        }
    }
}
