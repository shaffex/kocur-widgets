//
//  MyWidgetUtils.swift
//  MagicUIDemoApp
//
//  Created by Peter Popovec on 03/06/2026.
//

import WidgetKit
import Foundation
import MagicUI

public struct MyWidgetTimelineEntry: TimelineEntry {
        public let date: Date
        let configuration: ConfigurationAppIntent
        let family: WidgetFamily
        let widgetPostFix: String
        let xml: String
    }

enum NetworkServiceError: Error {
    case invalidURL
    case invalidResponse
}

struct NetworkService {
    static func fetchEntry(urlString: String, kind: String, family: WidgetFamily, configuration: ConfigurationAppIntent) async throws -> MyWidgetTimelineEntry {
        guard let url = URL(string: urlString) else {
            throw NetworkServiceError.invalidURL
        }

        let (data, response) = try await URLSession.shared.data(from: url)

        if let http = response as? HTTPURLResponse, !(200...299).contains(http.statusCode) {
            throw NetworkServiceError.invalidResponse
        }

        guard let xml = String(data: data, encoding: .utf8) else {
            throw NetworkServiceError.invalidResponse
        }

        saveXML(xml, kind: kind, family: family)

        // Download any remote `<image src="url:…">` to the on-disk cache now, while
        // we're still in async timeline-building. The widget view renders
        // synchronously and can't fetch images itself; it reads these cached files.
        await MagicImageCache.prefetch(markup: xml)

        return MyWidgetTimelineEntry(
            date: Date(),
            configuration: configuration,
            family: family,
            widgetPostFix: "network",
            xml: xml
        )
    }

    private static func saveXML(_ xml: String, kind: String, family: WidgetFamily) {
        let fileURL = cachedXMLURL(kind: kind, family: family)
        do {
            try xml.write(to: fileURL, atomically: true, encoding: .utf8)
            print("saved widget xml to \(fileURL.path)")
        } catch {
            print("failed to save widget xml: \(error)")
        }
    }

    static func loadXML(kind: String, family: WidgetFamily) -> String? {
        let fileURL = cachedXMLURL(kind: kind, family: family)
        return try? String(contentsOf: fileURL, encoding: .utf8)
    }

    private static func cachedXMLURL(kind: String, family: WidgetFamily) -> URL {
        let fileName = "\(kind)_\(family)_network.xml"
        let dir = FileManager.default.urls(for: .cachesDirectory, in: .userDomainMask).first!
        return dir.appendingPathComponent(fileName)
    }
}
